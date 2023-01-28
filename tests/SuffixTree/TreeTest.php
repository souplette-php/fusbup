<?php declare(strict_types=1);

namespace ju1ius\FusBup\Tests\SuffixTree;

use ju1ius\FusBup\Compiler\SuffixTreeBuilder;
use ju1ius\FusBup\Exception\UnknownOpcodeException;
use ju1ius\FusBup\Parser\Rule;
use ju1ius\FusBup\Parser\RuleType;
use ju1ius\FusBup\SuffixTree\Node;
use ju1ius\FusBup\SuffixTree\Tree;
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
    }

    public function testUnknownOpcode(): void
    {
        $tree = new Tree(new Node(0, [
            'com' => 42,
        ]));
        $this->expectException(UnknownOpcodeException::class);
        $tree->split('a.com');
    }
}
