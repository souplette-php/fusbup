<?php declare(strict_types=1);

namespace Souplette\FusBup\Compiler;

use Souplette\FusBup\Compiler\Dafsa\Dafsa;
use Souplette\FusBup\Compiler\Dafsa\Encoder;
use Souplette\FusBup\Compiler\Parser\RuleList;
use Souplette\FusBup\Compiler\Parser\RuleType;
use Souplette\FusBup\Compiler\Parser\Section;
use Souplette\FusBup\Compiler\Utils\ByteArray;
use Souplette\FusBup\Lookup\Dafsa\Result;
use Souplette\FusBup\Utils\Idn;

/**
 * @internal
 */
final class DafsaCompiler
{
    public function compile(RuleList $rules, bool $reverse = false): string
    {
        $words = self::createWordList($rules, $reverse);
        return $this->compileWords($words);
    }

    public function compileWords(array $words): string
    {
        $dafsa = Dafsa::of($words);
        $encoder = new Encoder();
        $bytes = $encoder->encode($dafsa);
        return ByteArray::toDafsa($bytes);
    }

    /**
     * @return string[]
     */
    private static function createWordList(RuleList $rules, bool $reverse = false): array
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
                true => strrev($domain) . \chr($flags & 0x0F),
                false => $domain . \chr($flags & 0x0F),
            };
        }
        return $words;
    }
}
