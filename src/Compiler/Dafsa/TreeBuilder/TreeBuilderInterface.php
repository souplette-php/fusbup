<?php declare(strict_types=1);

namespace ju1ius\FusBup\Compiler\Dafsa\TreeBuilder;

use ju1ius\FusBup\Compiler\Dafsa\Node;

interface TreeBuilderInterface
{
    /**
     * Generates a DAFSA from a word list and returns the source nodes.
     * Each word is split into characters,
     * so that each character is represented by a unique node.
     * The word list must not be empty.
     *
     * @param string[] $words
     * @return Node[]
     */
    public function build(array $words): array;
}
