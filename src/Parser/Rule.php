<?php declare(strict_types=1);

namespace ju1ius\FusBup\Parser;

use ju1ius\FusBup\Exception\ParseError;

/**
 * @internal
 */
final class Rule
{
    public array $labels;

    public function __construct(
        public string $suffix,
        public RuleType $type = RuleType::Default,
    ) {
        if (false === $canonical = idn_to_ascii($suffix)) {
            throw ParseError::idnError($suffix);
        }
        $this->labels = array_reverse(explode('.', $canonical));
    }
}
