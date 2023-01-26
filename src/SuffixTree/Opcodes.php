<?php declare(strict_types=1);

namespace ju1ius\FusBup\SuffixTree;

/**
 * @internal
 */
final class Opcodes
{
    /**
     * A label matches, and we must keep processing child nodes.
     */
    const CONTINUE = 1;
    /**
     * Regular rule: record a match at current path.
     */
    const STORE = 2;
    /**
     * Wildcard rule: record a match at current path + 1.
     */
    const WILDCARD = 3;
    /**
     * Exception rule: filter-out matches for current path.
     */
    const EXCLUDE = 4;
}
