<?php declare(strict_types=1);

namespace ju1ius\FusBup\Lookup\Dafsa;

/**
 * @internal
 */
final class Result
{
    // key is not in set
    const NotFound = -1;

    // key is in set
    const Found = 0b000;

    // key excluded from set via exception
    const Exception = 0b001;

    // key matched a wildcard rule
    const Wildcard = 0b010;

    // key matched a private rule
    const Private = 0b100;
}
