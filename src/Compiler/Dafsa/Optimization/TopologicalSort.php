<?php declare(strict_types=1);

namespace ju1ius\FusBup\Compiler\Dafsa\Optimization;

use ju1ius\FusBup\Compiler\Dafsa\Node;

/**
 * Sorts a list of nodes in topological order.
 */
final class TopologicalSort implements OptimizationPassInterface
{
    private \SplObjectStorage $incoming;

    public function process(array $nodes): array
    {
        $this->incoming = new \SplObjectStorage();
        foreach ($nodes as $node) {
            $this->countIncoming($node);
        }
        foreach ($nodes as $node) {
            if (!$node->isSink()) {
                $this->incoming[$node] -= 1;
            }
        }

        return $this->sort($nodes);
    }

    private function sort(array $nodes): array
    {
        $waiting = array_filter($nodes, fn($n) => !$n->isSink() && $this->incoming[$n] === 0);
        $sorted = [];
        while ($waiting) {
            $node = array_pop($waiting);
            assert($this->incoming[$node] === 0);
            $sorted[] = $node;
            foreach ($node->children as $child) {
                if (!$child->isSink()) {
                    $this->incoming[$child] -= 1;
                    if ($this->incoming[$child] === 0) {
                        $waiting[] = $child;
                    }
                }
            }
        }

        return $sorted;
    }

    private function countIncoming(Node $node): void
    {
        if ($node->isSink()) {
            return;
        }
        if (!$this->incoming->contains($node)) {
            $this->incoming[$node] = 1;
            foreach ($node->children as $child) {
                $this->countIncoming($child);
            }
        } else {
            $this->incoming[$node] += 1;
        }
    }
}
