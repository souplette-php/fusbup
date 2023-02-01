<?php declare(strict_types=1);

namespace ju1ius\FusBup\Compiler\Dafsa;

/**
 * @internal
 * State machine for inserting a word to a Dafsa.
 *
 * Each state returns a function reference to the "next state".
 * States should be invoked until "None" is returned,
 * in which case the new word has been inserted.
 *
 * The prefix and suffix indexes are placed according to the currently-known valid
 * value (not the next value being investigated). Additionally, they are 0-indexed
 * against the root node (which sits behind the beginning of the string).
 *
 * Let's imagine we're at the following state when adding, for example, the word "mozilla.org":
 *
 *       mozilla.org
 *      ^    ^    ^ ^
 *      |    |    | |
 *     /     |    | \
 *   [root]  |    |  [end] node
 *   node    |    \
 *           |     suffix
 *           \
 *            prefix
 *
 * In this state, the furthest prefix match we could find was:
 *   [root] - m - o - z - i - l
 * The index of the prefix match is "5".
 *
 * Additionally, we've been looking for suffix nodes, and we've already found:
 *   r - g - [end]
 * The current suffix index is "10".
 * The next suffix node we'll attempt to find is at index "9".
 */
final class InsertionStateMachine
{
    private int $prefixIndex = 0;
    private SuffixCursor $suffixCursor;
    /**
     * @var Node[]
     */
    private array $stack;
    private bool $suffixOverlapsPrefix = false;
    private ?int $firstForkIndex = null;
    private ?\Closure $state;

    public function __construct(
        public string $word,
        public Node $rootNode,
        Node $endNode,
    ) {
        $this->suffixCursor = new SuffixCursor(\strlen($this->word) + 1, $endNode);
        $this->stack = [$this->rootNode];
        $this->state = $this->findPrefix(...);
    }

    /**
     * Run this state machine to completion, adding the new word.
     */
    public function run(): void
    {
        while ($this->state) {
            $this->state = ($this->state)();
        }
    }

    /**
     * Find the longest existing prefix that matches the current word.
     */
    private function findPrefix(): ?\Closure
    {
        $prefixNode = $this->rootNode;
        while ($this->prefixIndex < \strlen($this->word)) {
            $nextChar = $this->word[$this->prefixIndex];
            if (!$nextNode = $prefixNode->children[$nextChar] ?? null) {
                // We're finished finding the prefix,
                // let's find the longest suffix match now.
                return $this->findSuffixNodesAfterPrefix(...);
            }
            $this->prefixIndex++;
            $prefixNode = $nextNode;
            $this->stack[] = $nextNode;
            if (!$this->firstForkIndex && $nextNode->isFork()) {
                $this->firstForkIndex = $this->prefixIndex;
            }
        }
        // Déjà vu, we've appended this string before. Since this string has
        // already been appended, we don't have to do anything.
        return null;
    }

    /**
     * Find the chain of suffix nodes for characters after the prefix.
     */
    private function findSuffixNodesAfterPrefix(): \Closure
    {
        while ($this->suffixCursor->index - 1 > $this->prefixIndex) {
            // To fetch the next character, we need to subtract two from the current
            // suffix index. This is because:
            //   * The next suffix node is 1 node before our current node (subtract 1)
            //   * The suffix index includes the root node before the beginning of the
            //     string - it's like the string is 1-indexed (subtract 1 again).
            $nextChar = $this->word[$this->suffixCursor->index - 2];
            if (!$this->suffixCursor->findSingleChild($nextChar)) {
                return $this->addNewNodes(...);
            }
            if ($this->suffixCursor->node === end($this->stack)) {
                // The suffix match is overlapping with the prefix! This can happen in
                // cases like:
                // * "ab"
                // * "abb"
                // The suffix cursor is at the same node as the prefix match, but they're
                // at different positions in the word.
                //
                // [root] - a - b - [end]
                //              ^
                //             / \
                //            /   \
                //      prefix     suffix
                //            \    /
                //             \  /
                //              VV
                //            "abb"
                if (!$this->firstForkIndex) {
                    // there hasn't been a fork, so our prefix isn't shared. so, we
                    // can mark this node as a fork, since the repetition means
                    // that there's two paths that are now using this node
                    $this->firstForkIndex = $this->prefixIndex;
                    return $this->addNewNodes(...);
                }
                // Removes the link between the unique part of the prefix and the
                // shared part of the prefix.
                $this->stack[$this->firstForkIndex]->removeParent(
                    $this->stack[$this->firstForkIndex - 1],
                );
                $this->suffixOverlapsPrefix = true;
            }
        }
        if ($this->firstForkIndex === null) {
            return $this->findNextSuffixNodes(...);
        }
        if ($this->suffixCursor->index - 1 === $this->firstForkIndex) {
            return $this->findNextSuffixNodeAtPrefixEndAtFork(...);
        }
        return $this->findNextSuffixNodeAtPrefixEndAfterFork(...);
    }

    /**
     * Find the next suffix node that replaces the end of the prefix.
     *
     * In this state, the prefix_end node is the same as the first fork node.
     * Therefore, if a match can be found, the old prefix node can't be entirely
     * deleted since it's used elsewhere. Instead, just the link between our
     * unique prefix and the end of the fork is removed.
     */
    private function findNextSuffixNodeAtPrefixEndAtFork(): \Closure
    {
        $existingNode = $this->stack[$this->prefixIndex];
        if (!$this->suffixCursor->findEndOfPrefixReplacement($existingNode)) {
            return $this->addNewNodes(...);
        }
        $this->prefixIndex--;
        $this->firstForkIndex = null;
        if (!$this->suffixOverlapsPrefix) {
            $existingNode->removeParent($this->stack[$this->prefixIndex]);
        } else {
            // When the suffix overlaps the prefix, the old "parent link" was removed
            // earlier in the "find_suffix_nodes_after_prefix" step.
            $this->suffixOverlapsPrefix = false;
        }
        return $this->findNextSuffixNodes(...);
    }

    /**
     * Find the next suffix node that replaces the end of the prefix.
     *
     * In this state, the prefix_end node is after the first fork node.
     * Therefore, even if a match is found, we don't want to modify the replaced
     * prefix node since an unrelated word chain uses it.
     */
    private function findNextSuffixNodeAtPrefixEndAfterFork(): \Closure
    {
        $existingNode = $this->stack[$this->prefixIndex];
        if (!$this->suffixCursor->findEndOfPrefixReplacement($existingNode)) {
            return $this->addNewNodes(...);
        }
        $this->prefixIndex--;
        if ($this->prefixIndex === $this->firstForkIndex) {
            return $this->findNextSuffixNodeWithinPrefixAtFork(...);
        }
        return $this->findNextSuffixNodesWithinPrefixAfterFork(...);
    }

    /**
     * Find the next suffix node within a prefix.
     *
     * In this state, we've already worked our way back and found nodes in the suffix
     * to replace prefix nodes after the fork node. We have now reached the fork node,
     * and if we find a replacement for it, then we can remove the link between it
     * and our then-unique prefix and clear the fork status.
     */
    private function findNextSuffixNodeWithinPrefixAtFork(): \Closure
    {
        $existingNode = $this->stack[$this->prefixIndex];
        if (!$this->suffixCursor->findInsideOfPrefixReplacement($existingNode)) {
            return $this->addNewNodes(...);
        }
        $this->prefixIndex--;
        $this->firstForkIndex = null;
        if (!$this->suffixOverlapsPrefix) {
            $existingNode->removeParent($this->stack[$this->prefixIndex]);
        } else {
            // When the suffix overlaps the prefix, the old "parent link" was removed
            // earlier in the "find_suffix_nodes_after_prefix" step.
            $this->suffixOverlapsPrefix = false;
        }
        return $this->findNextSuffixNodes(...);
    }

    /**
     * Find the next suffix nodes within a prefix.
     *
     * Finds suffix nodes to replace prefix nodes, but doesn't modify the prefix
     * nodes since they're after a fork (so, we're sharing prefix nodes with
     * other words and can't modify them).
     */
    private function findNextSuffixNodesWithinPrefixAfterFork(): ?\Closure
    {
        while (true) {
            $existingNode = $this->stack[$this->prefixIndex];
            if (!$this->suffixCursor->findInsideOfPrefixReplacement($existingNode)) {
                return $this->addNewNodes(...);
            }
            $this->prefixIndex--;
            if ($this->prefixIndex === $this->firstForkIndex) {
                return $this->findNextSuffixNodeWithinPrefixAtFork(...);
            }
        }
    }

    /**
     * Find all remaining suffix nodes in the chain.
     *
     * In this state, there's no (longer) any fork, so there's no other words
     * using our current prefix. Therefore, as we find replacement nodes as we
     * work our way backwards, we can remove the now-unused prefix nodes.
     */
    private function findNextSuffixNodes(): ?\Closure
    {
        while (true) {
            $existingNode = $this->stack[$this->prefixIndex];
            if (!$this->suffixCursor->findEndOfPrefixReplacement($existingNode)) {
                return $this->addNewNodes(...);
            }
            // This prefix node is wholly replaced by the new suffix node,
            // so it can be deleted.
            $existingNode->remove();
            $this->prefixIndex--;
        }
    }

    /**
     * Adds new nodes to support the new word.
     *
     * Duplicates forked nodes to make room for new links, adds new nodes for new
     * characters, and splices the prefix to the suffix to finish embedding the new
     * word into the DAFSA.
     */
    private function addNewNodes(): ?\Closure
    {
        if ($this->firstForkIndex !== null) {
            $frontNode = $this->duplicateForkNodes();
        } else {
            $frontNode = $this->stack[$this->prefixIndex];
        }
        // todo: check possible oboe
        $newText = substr($this->word, $this->prefixIndex, $this->suffixCursor->index - 1 - $this->prefixIndex);
        for ($i = 0; $i < \strlen($newText); $i++) {
            $char = $newText[$i];
            $newNode = Node::of($char);
            $frontNode->add($newNode);
            $frontNode = $newNode;
        }

        $frontNode->add($this->suffixCursor->node);
        // We're done!
        return null;
    }

    private function duplicateForkNodes(): Node
    {
        $parentNode = $this->stack[$this->firstForkIndex - 1];
        // if suffix_overlaps_parent, the parent link was removed
        // earlier in the word-adding process.
        if (!$this->suffixOverlapsPrefix) {
            $this->stack[$this->firstForkIndex]->removeParent($parentNode);
        }

        for ($i = $this->firstForkIndex; $i < $this->prefixIndex + 1; $i++) {
            $forkNode = $this->stack[$i];
            $replacementNode = Node::of($forkNode->char);
            $excluded = null;
            if ($i < \count($this->stack) - 1) {
                $excluded = $this->stack[$i - 1];
            }
            $replacementNode->fork($forkNode, $excluded);
            $parentNode->add($replacementNode);
            $parentNode = $replacementNode;
        }

        return $parentNode;
    }
}
