<?php declare(strict_types=1);

namespace ju1ius\FusBup\Parser;

/**
 * @internal
 */
enum RuleType
{
    case Default;
    case Wildcard;
    case Exception;
}
