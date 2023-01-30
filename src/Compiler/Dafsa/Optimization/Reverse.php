<?php declare(strict_types=1);

namespace ju1ius\FusBup\Compiler\Dafsa\Optimization;

use ju1ius\FusBup\Compiler\Dafsa\Node;

/**
 * Generates a new DAFSA that is reversed,
 * so that the old sink node becomes the new source node.
 */
final class Reverse implements OptimizationPassInterface
{
    private \SplObjectStorage $nodeMap;

    public function process(array $nodes): array
    {
        $this->nodeMap = new \SplObjectStorage();
        $sink = [];
        foreach ($nodes as $node) {
            $this->processNode($node, Node::sink(), $sink);
        }

        return $sink;
    }

    private function processNode(Node $node, Node $parent, array &$sink): void
    {
        if ($node->isSink()) {
            $sink[] = $parent;
        } elseif (!$this->nodeMap->contains($node)) {
            $this->nodeMap[$node] = Node::of($this->reverseValue($node->value), [$parent]);
            foreach ($node->children as $child) {
                $this->processNode($child, $this->nodeMap[$node], $sink);
            }
        } else {
            $this->nodeMap[$node]->children[] = $parent;
        }
    }

    /**
     * @todo reverse unicode strings?
     */
    private function reverseValue(string $value): string
    {
        return strrev($value);
    }
}
