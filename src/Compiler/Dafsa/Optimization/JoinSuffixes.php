<?php declare(strict_types=1);

namespace ju1ius\FusBup\Compiler\Dafsa\Optimization;

use ju1ius\FusBup\Compiler\Dafsa\Node;

/**
 * Generates a new DAFSA where nodes that represent
 * the same word lists towards the sink are merged.
 */
final class JoinSuffixes implements OptimizationPassInterface
{
    private array $nodeMap;

    public function process(array $nodes): array
    {
        $this->nodeMap = [
            '' => Node::sink(),
        ];
        return array_map($this->join(...), $nodes);
    }

    /**
     * Returns a matching node.
     * A new node is created if no matching node exists.
     * The graph is accessed in dfs order.
     */
    private function join(Node $node): Node
    {
        $key = implode('', $node->toWords());
        if (!isset($this->nodeMap[$key])) {
            // The only set of suffixes for the sink is {''},
            // contained in nodeMap.
            assert(!$node->isSink());
            $this->nodeMap[$key] = Node::of(
                $node->value,
                array_map($this->join(...), $node->children),
            );
        }

        return $this->nodeMap[$key];
    }
}
