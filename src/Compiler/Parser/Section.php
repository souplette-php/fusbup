<?php declare(strict_types=1);

namespace Souplette\FusBup\Compiler\Parser;

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
