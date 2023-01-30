<?php declare(strict_types=1);

namespace ju1ius\FusBup\Compiler\Dafsa;

use ju1ius\FusBup\Compiler\ByteArray;
use ju1ius\FusBup\Compiler\Dafsa\Encoder\AsciiEncoder;
use ju1ius\FusBup\Compiler\Dafsa\Optimization\JoinLabels;
use ju1ius\FusBup\Compiler\Dafsa\Optimization\JoinSuffixes;
use ju1ius\FusBup\Compiler\Dafsa\Optimization\Reverse;
use ju1ius\FusBup\Compiler\Dafsa\TreeBuilder\AsciiTreeBuilder;
use ju1ius\FusBup\Dafsa\Result;
use ju1ius\FusBup\Parser\Rule;
use ju1ius\FusBup\Parser\RuleType;
use ju1ius\FusBup\Parser\Section;
use ju1ius\FusBup\Utils\Idn;

final class DafsaCompiler
{
    /**
     * @param Rule[] $rules
     */
    public function compile(array $rules, bool $reverse = false): string
    {
        usort($rules, Rule::compare(...));
        $words = self::createWordList($rules, $reverse);
        return $this->compileWords($words);
    }

    public function compileWords(array $words): string
    {
        $dafsa = (new AsciiTreeBuilder())->build($words);
        $optimizations = [
            new Reverse(),
            new JoinSuffixes(),
            new Reverse(),
            new JoinSuffixes(),
            new JoinLabels(),
        ];
        foreach ($optimizations as $pass) {
            $dafsa = $pass->process($dafsa);
        }

        $encoder = new AsciiEncoder();
        $bytes = $encoder->encode($dafsa);
        return ByteArray::toDafsa($bytes);
    }

    /**
     * @param Rule[] $rules
     * @return string[]
     */
    private static function createWordList(array $rules, bool $reverse = false): array
    {
        $words = [];
        foreach ($rules as $rule) {
            $domain = Idn::toAscii($rule->suffix);
            $flags = match ($rule->type) {
                RuleType::Default => 0,
                RuleType::Exception => Result::Exception,
                RuleType::Wildcard => Result::Wildcard,
            };
            $flags |= match ($rule->section) {
                Section::Icann => 0,
                // anything other than ICANN we set to private
                default => Result::Private,
            };
            $words[] = match ($reverse) {
                true => strrev($domain) . ($flags & 0x0F),
                false => $domain . ($flags & 0x0F),
            };
        }
        return $words;
    }
}
