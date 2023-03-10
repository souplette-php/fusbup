<?php declare(strict_types=1);

namespace Souplette\FusBup\Lookup\SuffixTree;

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
