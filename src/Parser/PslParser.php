<?php declare(strict_types=1);

namespace ju1ius\FusBup\Parser;

use ju1ius\FusBup\Exception\ParseError;
use SplFileObject;
use Traversable;

final class PslParser
{
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
        $rules = [];
        foreach ($lines as $line) {
            // TODO: handle ===BEGIN SECTION=== comments
            if (str_starts_with($line, '//')) continue;
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
        return new Rule($m['suffix'], match ($m['prefix']) {
            '*.' => RuleType::Wildcard,
            '!' => RuleType::Exception,
            '' => RuleType::Default,
        });
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
