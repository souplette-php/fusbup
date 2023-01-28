<?php declare(strict_types=1);

namespace ju1ius\FusBup\Parser;

use ju1ius\FusBup\Exception\IdnException;
use ju1ius\FusBup\Exception\ParseError;
use ju1ius\FusBup\Utils\Idn;

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
        try {
            $canonical = Idn::toAscii($this->suffix);
        } catch (IdnException $err) {
            throw ParseError::from($err, "Invalid suffix: {$suffix}");
        }
        $this->labels = array_reverse(explode('.', $canonical));
    }
}
