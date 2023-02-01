<?php declare(strict_types=1);

namespace ju1ius\FusBup\Compiler\Parser;

/**
 * @internal
 */
enum Section
{
    case None;
    case Icann;
    case Private;
    case Unknown;
}
