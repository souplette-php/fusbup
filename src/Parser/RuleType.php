<?php declare(strict_types=1);

namespace ju1ius\FusBup\Parser;

/**
 * @internal
 */
enum RuleType: int
{
    case Default = 0;
    case Wildcard = 1;
    case Exception = 2;

    public function compare(self $other): int
    {
        return $this->value <=> $other->value;
    }
}
