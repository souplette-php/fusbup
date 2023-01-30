<?php declare(strict_types=1);

namespace ju1ius\FusBup\Compiler\Dafsa\Encoder;

use ju1ius\FusBup\Compiler\Dafsa\Node;

interface EncoderInterface
{
    /**
     * Encodes a list of nodes to a list of bytes.
     *
     * @param Node[] $nodes
     * @return int[]
     */
    public function encode(array $nodes): array;
}
