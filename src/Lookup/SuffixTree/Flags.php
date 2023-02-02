<?php declare(strict_types=1);

namespace ju1ius\FusBup\Lookup\SuffixTree;

/**
 * @internal
 */
final class Flags
{
    /**
     * A label matches, and we must keep processing child nodes.
     */
    const CONTINUE = 0;
    /**
     * Regular rule: record a match at current path.
     */
    const STORE = 1;
    /**
     * Wildcard rule: record a match at current path + 1.
     */
    const WILDCARD = 2;
    /**
     * Exception rule: filter-out matches for current path.
     */
    const EXCLUDE = 4;
    /**
     * Private rule (outside the ICANN section).
     */
    const PRIVATE = 8;
}
