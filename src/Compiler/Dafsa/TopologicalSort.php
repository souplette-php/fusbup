<?php declare(strict_types=1);

namespace ju1ius\FusBup\Compiler\Dafsa;

/**
 * @internal
 */
final class TopologicalSort
{
    private \SplObjectStorage $incoming;

    public function process(Dafsa $dafsa): array
    {
        $this->incoming = new \SplObjectStorage();
        foreach ($dafsa as $node) {
            $this->countIncoming($node);
        }
        foreach ($dafsa as $node) {
            if ($node->isSink) continue;
            $this->incoming[$node] -= 1;
        }
        return $this->sort($dafsa);
    }

    private function sort(Dafsa $dafsa): array
    {
        $waiting = array_filter(
            $dafsa->rootNode->children,
            fn(Node $n) => !$n->isSink && $this->incoming[$n] === 0,
        );

        $sorted = [];
        while ($waiting) {
            $node = array_pop($waiting);
            assert($this->incoming[$node] === 0);
            $sorted[] = $node;
            foreach ($node->children as $child) {
                if ($child->isSink) continue;
                $this->incoming[$child] -= 1;
                if ($this->incoming[$child] === 0) {
                    $waiting[] = $child;
                }
            }
        }

        return $sorted;
    }

    private function countIncoming(Node $node): void
    {
        if ($node->isSink) {
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
