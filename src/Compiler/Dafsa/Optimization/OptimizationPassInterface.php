<?php declare(strict_types=1);

namespace ju1ius\FusBup\Compiler\Dafsa\Optimization;

use ju1ius\FusBup\Compiler\Dafsa\Node;

interface OptimizationPassInterface
{
    /**
     * @param Node[] $nodes
     * @return Node[]
     */
    public function process(array $nodes): array;
}
