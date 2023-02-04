<?php declare(strict_types=1);

namespace ju1ius\FusBup\Tests\Lookup;

use ju1ius\FusBup\Compiler\Parser\Rule;
use ju1ius\FusBup\Compiler\Parser\RuleType;
use ju1ius\FusBup\Exception\PrivateETLDException;
use ju1ius\FusBup\Exception\UnknownTLDException;
use ju1ius\FusBup\Lookup\LookupInterface;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

abstract class LookupTestCase extends TestCase
{
    abstract protected static function compile(array $rules): LookupInterface;

    #[DataProvider('splitProvider')]
    public function testSplit(array $rules, string $domain, array $expected): void
    {
        $lookup = static::compile($rules);
        $result = $lookup->split($domain);
        Assert::assertSame($expected, $result);
    }

    #[DataProvider('splitProvider')]
    public function testIsPublicSuffix(array $rules, string $domain, array $expected): void
    {
        $lookup = static::compile($rules);
        [$head, $tail] = $expected;
        $result = $lookup->isEffectiveTLD($domain);
        $expectPublic = !$head && $tail;
        Assert::assertSame($expectPublic, $result);
    }

    #[DataProvider('splitProvider')]
    public function testGetPublicSuffix(array $rules, string $domain, array $expected): void
    {
        $lookup = static::compile($rules);
        [, $tail] = $expected;
        $result = $lookup->getEffectiveTLD($domain);
        Assert::assertSame(implode('.', $tail), $result);
    }

    public static function splitProvider(): iterable
    {
        yield 'no match uses * as default' => [
            [new Rule('a.b'), new Rule('b.c')],
            'foo.bar',
            [['foo'], ['bar']]
        ];
        yield 'single non-ambiguous match' => [
            [new Rule('a.b'), new Rule('b.c')],
            'foo.bar.b.c',
            [['foo', 'bar'], ['b', 'c']],
        ];
        yield 'labels are canonicalized' => [
            [new Rule('aérôpört.ci')],
            'VAMOS.AL.AÉRÔPÖRT.CI',
            [['vamos', 'al'], ['xn--arprt-bsa2fra', 'ci']],
        ];
        yield 'several matches' => [
            [new Rule('uk'), new Rule('co.uk')],
            'a.b.co.uk',
            [['a', 'b'], ['co', 'uk']],
        ];
        yield 'several matches, rule order is irrelevant' => [
            [new Rule('co.uk'), new Rule('uk')],
            'a.b.co.uk',
            [['a', 'b'], ['co', 'uk']],
        ];
        yield 'wildcard rule' => [
            [new Rule('com', RuleType::Wildcard)],
            'a.b.com',
            [['a'], ['b', 'com']],
        ];
        yield 'wildcard rule when nothing matches *' => [
            [new Rule('foo.com', RuleType::Wildcard)],
            'foo.com',
            [[], ['foo', 'com']],
        ];
        yield 'exclusion rule wins over wildcard' => [
            [new Rule('test', RuleType::Wildcard), new Rule('www.test', RuleType::Exception)],
            'www.test',
            [['www'], ['test']],
        ];
        yield 'exclusion rule' => [
            [
                new Rule('com'),
                new Rule('yep.com', RuleType::Wildcard),
                new Rule('nope.yep.com', RuleType::Exception),
            ],
            'nope.yep.com',
            [['nope'], ['yep', 'com']],
        ];
        yield 'a / *.a matches a' => [
            $ruleSet1 = [
                new Rule('a'),
                new Rule('a', RuleType::Wildcard),
            ],
            'a',
            [[], ['a']],
        ];
        yield 'a / *.a matches b.a' => [
            $ruleSet1,
            'b.a',
            [[], ['b', 'a']],
        ];
        yield 'wildcards imply a registered parent domain' => [
            [
                new Rule('a'),
                new Rule('b.a', RuleType::Wildcard),
            ],
            'b.a',
            [[], ['b', 'a']],
        ];
    }

    #[DataProvider('providePrivateDomainErrorCases')]
    public function testIsEffectiveTLDReturnsFalseForPrivateEntries(array $rules, string $domain): void
    {
        $lookup = static::compile($rules);
        Assert::assertFalse($lookup->isEffectiveTLD($domain, $lookup::FORBID_PRIVATE));
    }

    #[DataProvider('provideUnknownDomainErrorCases')]
    public function testIsEffectiveTLDReturnsFalseForUnknownTLD(array $rules, string $domain): void
    {
        $lookup = static::compile($rules);
        Assert::assertFalse($lookup->isEffectiveTLD($domain, $lookup::FORBID_UNKNOWN));
    }

    #[DataProvider('providePrivateDomainErrorCases')]
    public function testEffectiveLTDDisallowPrivate(array $rules, string $domain): void
    {
        $lookup = static::compile($rules);
        $this->expectException(PrivateETLDException::class);
        $lookup->getEffectiveTLD($domain, $lookup::FORBID_PRIVATE);
    }

    #[DataProvider('provideUnknownDomainErrorCases')]
    public function testEffectiveLTDDisallowUnknown(array $rules, string $domain): void
    {
        $lookup = static::compile($rules);
        $this->expectException(UnknownTLDException::class);
        $lookup->getEffectiveTLD($domain, $lookup::FORBID_UNKNOWN);
    }

    #[DataProvider('providePrivateDomainErrorCases')]
    public function testSplitDisallowPrivate(array $rules, string $domain): void
    {
        $lookup = static::compile($rules);
        $this->expectException(PrivateETLDException::class);
        $lookup->split($domain, $lookup::FORBID_PRIVATE);
    }

    #[DataProvider('provideUnknownDomainErrorCases')]
    public function testSplitDisallowUnknown(array $rules, string $domain): void
    {
        $lookup = static::compile($rules);
        $this->expectException(UnknownTLDException::class);
        $lookup->split($domain, $lookup::FORBID_UNKNOWN);
    }

    public static function providePrivateDomainErrorCases(): iterable
    {
        yield 'private tld' => [
            [new Rule('a'), Rule::pub('b.a')],
            'b.a',
        ];
        yield 'private etld' => [
            [Rule::pub('a'), new Rule('b.a')],
            'b.a',
        ];
    }

    public static function provideUnknownDomainErrorCases(): iterable
    {
        yield [
            [Rule::pub('a')],
            'b.c',
        ];
    }
}
