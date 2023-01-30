<?php declare(strict_types=1);

namespace ju1ius\FusBup\Compiler\Dafsa\Optimization;

use ju1ius\FusBup\Compiler\Dafsa\Node;

/**
 * Generates a new DAFSA where internal nodes are merged
 * if there is a one to one connection.
 */
final class JoinLabels implements OptimizationPassInterface
{
    private \SplObjectStorage $parentCounts;
    private \SplObjectStorage $nodeMap;

    public function process(array $nodes): array
    {
        $this->parentCounts = new \SplObjectStorage();
        $this->parentCounts[Node::sink()] = 2;

        $this->nodeMap = new \SplObjectStorage();
        $this->nodeMap[Node::sink()] = Node::sink();

        foreach ($nodes as $node) {
            $this->countParents($node);
        }

        return array_map($this->join(...), $nodes);
    }

    private function join(Node $node): Node
    {
        if (!$this->nodeMap->contains($node)) {
            // nodeMap statically contains `DafsaNode::sink()`
            assert(!$node->isSink());
            /** @var Node[] $children */
            $children = array_map($this->join(...), $node->children);
            if (\count($children) === 1 && $this->parentCounts[$node->children[0]] === 1) {
                $child = $children[0];
                // parentCounts statically maps `DafsaNode::sink()` to 2,
                // so this child cannot be the sink.
                assert(!$child->isSink());
                $this->nodeMap[$node] = Node::of(
                    $node->value . $child->value,
                    $child->children
                );
            } else {
                $this->nodeMap[$node] = Node::of($node->value, $children);
            }
        }

        return $this->nodeMap[$node];
    }

    private function countParents(Node $node): void
    {
        if ($this->parentCounts->contains($node)) {
            $this->parentCounts[$node] += 1;
        } else {
            // parentCounts statically contains `DafsaNode::sink()`
            assert(!$node->isSink());
            $this->parentCounts[$node] = 1;
            foreach ($node->children as $child) {
                $this->countParents($child);
            }
        }
    }
}
