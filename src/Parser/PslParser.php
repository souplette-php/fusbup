<?php declare(strict_types=1);

namespace ju1ius\FusBup\Parser;

use ju1ius\FusBup\Exception\ParseError;
use SplFileObject;
use Traversable;

final class PslParser
{
    private Section $currentSection;

    /**
     * @return Rule[]
     */
    public function parse(string|SplFileObject $input): array
    {
        return $this->parseLines(self::lines($input));
    }

    /**
     * @param iterable<string> $lines
     * @return Rule[]
     */
    private function parseLines(iterable $lines): array
    {
        $this->currentSection = Section::None;
        $rules = [];
        foreach ($lines as $line) {
            if (str_starts_with($line, '//')) {
                $this->handleComment($line);
                continue;
            }
            $rules[] = $this->parseRule($line);
        }

        return $rules;
    }

    private const RULE_RX = <<<'REGEXP'
    /^
        (?<prefix> ! | \*\. )?
        (?<suffix> [^!*\s]+ )
    $/x
    REGEXP;

    private function parseRule(string $line): Rule
    {
        if (!preg_match(self::RULE_RX, $line, $m)) {
            throw ParseError::invalidRule($line);
        }
        $type = match ($m['prefix']) {
            '*.' => RuleType::Wildcard,
            '!' => RuleType::Exception,
            '' => RuleType::Default,
        };
        return new Rule($m['suffix'], $type, $this->currentSection);
    }

    private const SECTION_RX = <<<'REGEXP'
    ~^
        // \s+ === (?<action> BEGIN|END ) \s+
        (?<id> \w+ )
        \s+ DOMAINS ===
    $~x
    REGEXP;

    private function handleComment(string $line): void
    {
        if (!preg_match(self::SECTION_RX, $line, $m)) {
            return;
        }
        if ($m['action'] === 'END') {
            $this->currentSection = Section::None;
            return;
        }
        $this->currentSection = match ($m['id']) {
            'ICANN' => Section::Icann,
            'PRIVATE' => Section::Private,
            default => Section::Unknown,
        };
    }

    /**
     * @param string|SplFileObject $input
     * @return Traversable
     */
    private static function lines(string|SplFileObject $input): Traversable
    {
        $lines = match (true) {
            $input instanceof SplFileObject => new \NoRewindIterator($input),
            default => explode("\n", $input),
        };
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '') {
                yield $line;
            }
        }
    }
}
