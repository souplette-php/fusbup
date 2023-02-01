<?php declare(strict_types=1);

namespace ju1ius\FusBup\Compiler\Dafsa;

/**
 * @internal
 */
final class SuffixCursor
{
    public function __construct(
        public int $index,
        public Node $node,
    ) {
    }

    /**
     * Finds the next matching suffix node that has a single child.
     */
    public function findSingleChild(string $char): bool
    {
        return $this->query($char, fn(Node $n) => \count($n->children) === 1);
    }

    /**
     * Finds the next matching suffix node that replaces the old prefix-end node.
     */
    public function findEndOfPrefixReplacement(Node $endOfPrefix): bool
    {
        return $this->query(
            $endOfPrefix->char,
            fn(Node $n) => $n->isReplacementForPrefixEndNode($endOfPrefix),
        );
    }

    /**
     * Finds the next matching suffix node that replaces a node within the prefix.
     */
    public function findInsideOfPrefixReplacement(Node $prefixNode): bool
    {
        return $this->query(
            $prefixNode->char,
            fn(Node $n) => $n->isReplacementForPrefixNode($prefixNode),
        );
    }

    /**
     * @param string $char
     * @param callable(Node):bool $predicate
     * @return bool
     */
    private function query(string $char, callable $predicate): bool
    {
        foreach ($this->node->parents[$char] ?? [] as $node) {
            if ($predicate($node)) {
                $this->index -= 1;
                $this->node = $node;
                return true;
            }
        }
        return false;
    }
}
