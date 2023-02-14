<?php declare(strict_types=1);

namespace Souplette\FusBup\Compiler\Dafsa;

/**
 * @internal
 */
final class Node
{
    /** @var array<string, self> */
    public array $children = [];

    /** @var array<string, self[]> */
    public array $parents = [];

    private function __construct(
        public string $char,
        public readonly bool $isRoot = false,
        public readonly bool $isSink = false,
    ) {
    }

    public static function of(string $char): self
    {
        if ($char === '') {
            throw new \LogicException('Empty char.');
        }
        return new self($char);
    }

    public static function source(): self
    {
        return new self('', true);
    }

    public static function sink(): self
    {
        return new self('', isSink: true);
    }

    public function add(self $child): void
    {
        $this->children[$child->char] = $child;
        $child->parents[$this->char][] = $this;
    }

    /**
     * remove() must only be called when this node has only a single parent,
     * and that parent doesn't need this child anymore.
     * The caller is expected to have performed this validation.
     * (placing asserts here add a non-trivial performance hit)
     */
    public function remove(): void
    {
        assert(\count($this->parents) === 1);
        // There's only a single parent, so only one list should be in the "parents" map
        $parents = reset($this->parents);
        assert(\count($parents) === 1);
        $this->removeParent(reset($parents));
        foreach ($this->children as $child) {
            $child->removeParent($this);
        }
    }

    public function removeParent(self $parent): void
    {
        // remove $this inside $parent
        unset($parent->children[$this->char]);
        // remove $parent inside $this->parents
        $parentsForChar = array_filter($this->parents[$parent->char], fn(self $node) => $node !== $parent);
        if (!$parentsForChar) {
            unset($this->parents[$parent->char]);
        } else {
            $this->parents[$parent->char] = $parentsForChar;
        }
    }

    /**
     * Shallow-copies a node's children.
     *
     * When adding a new word, sometimes previously-joined suffixes aren't perfect matches anymore.
     * When this happens, some nodes need to be "copied" out.
     * For all non-end nodes, there's a child to exclude from the shallow-copy.
     */
    public function fork(self $node, ?self $excluded = null): void
    {
        foreach ($node->children as $child) {
            if ($child !== $excluded) {
                $this->add($child);
            }
        }
    }

    /**
     * Checks if this node has multiple parents.
     */
    public function isFork(): bool
    {
        if (!$this->parents) {
            return false;
        }
        if (\count($this->parents) > 1) {
            return true;
        }

        return \count(reset($this->parents)) > 1;
    }

    /**
     * Checks if this node is a valid replacement for an old end node.
     *
     * A node is a valid replacement if it maintains all existing child paths
     * while adding the new child path needed for the new word.
     */
    public function isReplacementForPrefixEndNode(self $old): bool
    {
        if (\count($this->children) !== \count($old->children) + 1) {
            return false;
        }
        foreach ($old->children as $char => $oldChild) {
            $child = $this->children[$char] ?? null;
            if ($oldChild !== $child) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if this node is a valid replacement for a non-end node.
     *
     * A node is a valid replacement if it:
     *   - Has one new child that the old node doesn't
     *   - Is missing a child that the old node has
     *   - Shares all other children
     */
    public function isReplacementForPrefixNode(self $old): bool
    {
        if (\count($this->children) !== \count($old->children)) {
            return false;
        }

        $foundExtraChild = false;
        foreach ($old->children as $char => $oldChild) {
            $child = $this->children[$char] ?? null;
            if ($oldChild !== $child) {
                if ($foundExtraChild) {
                    // Found two children in the old node that aren't in the new one,
                    // this isn't a valid replacement
                    return false;
                }
                $foundExtraChild = true;
            }
        }

        return $foundExtraChild;
    }
}
