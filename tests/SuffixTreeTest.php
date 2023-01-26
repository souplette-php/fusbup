<?php declare(strict_types=1);

namespace ju1ius\FusBup\Tests;

use ju1ius\FusBup\Parser\Rule;
use ju1ius\FusBup\Parser\RuleType;
use ju1ius\FusBup\Suffix\Tree;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class SuffixTreeTest extends TestCase
{
    /**
     * @dataProvider matchesProvider
     */
    public function testMatches(array $rules, string $domain, array $expected): void
    {
        $tree = Tree::of($rules);
        $matches = $tree->match($domain);
        Assert::assertEquals($expected, $matches);
    }

    public static function matchesProvider(): iterable
    {
        yield 'single non-ambiguous match' => [
            [new Rule('a.b'), new Rule('b.c')],
            'foo.bar.b.c',
            ['b.c']
        ];
        yield 'matches are unicode case-insensitive' => [
            [new Rule('aérôpört.ci')],
            'VAMOS.AL.AÉRÔPÖRT.CI',
            ['aérôpört.ci'],
        ];
        yield 'several matches' => [
            [new Rule('uk'), new Rule('co.uk')],
            'a.b.co.uk',
            ['uk', 'co.uk'],
        ];
        yield 'several matches, rule order is irrelevant' => [
            [new Rule('co.uk'), new Rule('uk')],
            'a.b.co.uk',
            ['uk', 'co.uk'],
        ];
        yield 'wildcard rule' => [
            [new Rule('com', RuleType::Wildcard)],
            'a.b.com',
            ['b.com'],
        ];
        yield 'exclusion rule wins over wildcard' => [
            [new Rule('test', RuleType::Wildcard), new Rule('www.test', RuleType::Exception)],
            'www.test',
            ['test'],
        ];
        yield 'wildcard rule doesnt match if no label' => [
            [new Rule('foo.com', RuleType::Wildcard)],
            'foo.com',
            ['com'],
        ];
        yield 'no match uses * as default' => [
            [new Rule('a.b'), new Rule('b.c')],
            'foo.bar',
            ['bar']
        ];
    }
}
