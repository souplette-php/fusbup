<?php declare(strict_types=1);

namespace ju1ius\FusBup\Parser;

use ju1ius\FusBup\Exception\IdnException;
use ju1ius\FusBup\Exception\ParseError;
use ju1ius\FusBup\Utils\Idn;

/**
 * @internal
 */
final class Rule implements \Stringable
{
    public array $labels;

    public function __construct(
        public string $suffix,
        public RuleType $type = RuleType::Default,
        public Section $section = Section::None,
    ) {
        try {
            $canonical = Idn::toAscii($this->suffix);
        } catch (IdnException $err) {
            throw ParseError::from($err, "Invalid suffix: {$suffix}");
        }
        $this->labels = array_reverse(explode('.', $canonical));
    }

    public static function pub(string $suffix, RuleType $type = RuleType::Default): self
    {
        return new self($suffix, $type, Section::Icann);
    }

    public static function priv(string $suffix, RuleType $type = RuleType::Default): self
    {
        return new self($suffix, $type, Section::Private);
    }

    /**
     * @link https://github.com/publicsuffix/list/wiki/Format#right-to-left-sorting
     */
    public static function compare(self $a, self $b): int
    {
        $diff = $a->getSortKey() <=> $b->getSortKey();
        if ($diff === 0) {
            return $a->type->compare($b->type);
        }
        return $diff;
    }

    public function __toString(): string
    {
        $type = match ($this->type) {
            RuleType::Default => '',
            RuleType::Wildcard => '*.',
            RuleType::Exception => '!',
        };

        return $type . $this->suffix;
    }

    private function getSortKey(): string
    {
        return implode('.', array_reverse(explode('.', $this->suffix)));
    }
}
