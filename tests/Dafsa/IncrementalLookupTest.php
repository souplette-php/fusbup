<?php declare(strict_types=1);

namespace ju1ius\FusBup\Tests\Dafsa;

use ju1ius\FusBup\Compiler\Utils\ByteArray;
use ju1ius\FusBup\Dafsa\IncrementalLookup;
use ju1ius\FusBup\Tests\ResourceHelper;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class IncrementalLookupTest extends TestCase
{
    private static function loadGraph(string $name): string
    {
        $path = ResourceHelper::path("dafsa/chromium/{$name}.php");
        return ByteArray::toString(require $path);
    }

    /**
     * @dataProvider singleRuleProvider
     */
    public function testSingleRule(string $input, int $expected): void
    {
        $graph = "\x81jp\x80";
        $result = IncrementalLookup::lookup($graph, $input);
        Assert::assertSame($expected, $result);
    }

    public static function singleRuleProvider(): iterable
    {
        yield ['', -1];
        yield ['j', -1];
        yield ['jp', 0];
        yield ['jjp', -1];
        yield ['jpp', -1];
    }

    /**
     * @dataProvider oneByteOffsetsProvider
     */
    public function testOneByteOffsets(string $input, int $expected): void
    {
        $graph = self::loadGraph('effective_tld_names_unittest1');
        $result = IncrementalLookup::lookup($graph, $input);
        Assert::assertSame($expected, $result);
    }

    public static function oneByteOffsetsProvider(): iterable
    {
        yield ['', -1];
        yield ['j', -1];
        yield ['jp', 0];
        yield ['jjp', -1];
        yield ['jpp', -1];
        yield ['bar.jp', 2];
        yield ['pref.bar.jp', 1];
        yield ['c', 2];
        yield ['b.c', 1];
        yield ['priv.no', 4];
    }

    /**
     * This DAFSA is constructed so that labels begin and end with unique characters,
     * which makes it impossible to merge labels.
     * Each inner node is about 100 bytes and a one byte offset can add at most 64 bytes
     * to previous offset.
     * Thus the paths must go over two byte offsets.
     *
     * @dataProvider twoBytesOffsetsProvider
     */
    public function testTwoBytesOffsets(string $input, int $expected): void
    {
        $graph = self::loadGraph('effective_tld_names_unittest3');
        $result = IncrementalLookup::lookup($graph, $input);
        Assert::assertSame($expected, $result);
    }

    public static function twoBytesOffsetsProvider(): iterable
    {
        yield [
            '0____________________________________________________________________________________________________0',
            0,
        ];
        yield [
            '7____________________________________________________________________________________________________7',
            4,
        ];
        yield [
            'a____________________________________________________________________________________________________8',
            -1,
        ];
    }

    /**
     * This DAFSA is constructed so that labels begin and end with unique characters,
     * which makes it impossible to merge labels.
     * The byte array has a size of ~54k.
     * A two byte offset can add at most add 8k to the previous offset.
     * Since we can skip only forward in memory, the nodes representing the return values
     * must be located near the end of the byte array.
     * The probability that we can reach from an arbitrary inner node
     * to a return value without using a three byte offset is small (but not zero).
     * The test is repeated with some different keys and with a reasonable probability
     * that at least one of the tested paths has go over a three byte offset.
     *
     * @dataProvider threeBytesOffsetsProvider
     */
    public function testThreeBytesOffsets(string $input, int $expected): void
    {
        $graph = self::loadGraph('effective_tld_names_unittest4');
        $result = IncrementalLookup::lookup($graph, $input);
        Assert::assertSame($expected, $result);
    }

    public static function threeBytesOffsetsProvider(): iterable
    {
        yield [
            'Z6____________________________________________________________________________________________________Z6',
            0,
        ];
        yield [
            'Z7____________________________________________________________________________________________________Z7',
            4,
        ];
        yield [
            'Za____________________________________________________________________________________________________Z8',
            -1,
        ];
    }

    /**
     * @dataProvider joinedPrefixesProvider
     */
    public function testJoinedPrefixes(string $input, int $expected): void
    {
        $graph = self::loadGraph('effective_tld_names_unittest5');
        $result = IncrementalLookup::lookup($graph, $input);
        Assert::assertSame($expected, $result);
    }

    public static function joinedPrefixesProvider(): iterable
    {
        yield ['ai', 0];
        yield ['bj', 4];
        yield ['aak', 0];
        yield ['bbl', 4];
        yield ['aaa', -1];
        yield ['bbb', -1];
        yield ['aaaam', 0];
        yield ['bbbbn', 0];
    }

    /**
     * @dataProvider joinedSuffixesProvider
     */
    public function testJoinedSuffixes(string $input, int $expected): void
    {
        $graph = self::loadGraph('effective_tld_names_unittest6');
        $result = IncrementalLookup::lookup($graph, $input);
        Assert::assertSame($expected, $result);
    }

    public static function joinedSuffixesProvider(): iterable
    {
        yield ['ia', 0];
        yield ['jb', 4];
        yield ['kaa', 0];
        yield ['lbb', 4];
        yield ['aaa', -1];
        yield ['bbb', -1];
        yield ['maaaa', 0];
        yield ['nbbbb', 0];
    }
}
