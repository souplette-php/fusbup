<?php declare(strict_types=1);

namespace ju1ius\FusBup\Tests\Lookup;

use ju1ius\FusBup\Compiler\SuffixTree\SuffixTreeBuilder;
use ju1ius\FusBup\Exception\PrivateDomainException;
use ju1ius\FusBup\Exception\UnknownDomainException;
use ju1ius\FusBup\Lookup\SuffixTree;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class SuffixTreeTest extends TestCase
{
    private static function compile(array $rules): SuffixTree
    {
        return SuffixTreeBuilder::build($rules);
    }

    /**
     * @dataProvider splitProvider
     */
    public function testSplit(array $rules, string $domain, array $expected): void
    {
        $tree = self::compile($rules);
        $result = $tree->split($domain);
        Assert::assertSame($expected, $result);
    }

    public static function splitProvider(): iterable
    {
        yield from PslLookupTestProvider::splitCases();
    }

    /**
     * @dataProvider splitDisallowPrivateProvider
     */
    public function testSplitDisallowPrivate(array $rules, string $domain): void
    {
        $tree = self::compile($rules);
        $this->expectException(PrivateDomainException::class);
        $tree->split($domain, $tree::ALLOW_NONE);
    }

    public static function splitDisallowPrivateProvider(): iterable
    {
        yield from PslLookupTestProvider::privateDomainErrorCases();
    }

    /**
     * @dataProvider splitDisallowUnknownProvider
     */
    public function testSplitDisallowUnknown(array $rules, string $domain): void
    {
        $tree = self::compile($rules);
        $this->expectException(UnknownDomainException::class);
        $tree->split($domain, $tree::ALLOW_NONE);
    }

    public static function splitDisallowUnknownProvider(): iterable
    {
        yield from PslLookupTestProvider::unknownDomainErrorCases();
    }
}
