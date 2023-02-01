<?php declare(strict_types=1);

namespace ju1ius\FusBup\Tests\SuffixTree;

use ju1ius\FusBup\Compiler\SuffixTree\SuffixTreeBuilder;
use ju1ius\FusBup\SuffixTree\Tree;
use ju1ius\FusBup\Tests\PslLookupTestProvider;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class TreeTest extends TestCase
{
    private static function compile(array $rules): Tree
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
}
