<?php declare(strict_types=1);

namespace ju1ius\FusBup\SuffixTree;

/**
 * @internal
 */
final class Node
{
    public function __construct(
        public int $flags,
        /** @var array<string, self|int> */
        public array $children,
    ) {
    }
}
