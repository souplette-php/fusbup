<?php declare(strict_types=1);

namespace Souplette\FusBup\Tests\Compiler\Parser;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Souplette\FusBup\Compiler\Parser\Rule;
use Souplette\FusBup\Compiler\Parser\RuleType;
use Souplette\FusBup\Compiler\Parser\Section;

final class RuleTest extends TestCase
{
    public function testPubConstructor(): void
    {
        $rule = Rule::pub('foo');
        Assert::assertSame(Section::Icann, $rule->section);
    }

    #[DataProvider('toStringProvider')]
    public function testToString(Rule $input, string $expected): void
    {
        Assert::assertSame($expected, (string)$input);
    }

    public static function toStringProvider(): iterable
    {
        yield 'normal rule' => [
            new Rule('a.b.c'),
            'a.b.c',
        ];
        yield 'wildcard rule' => [
            new Rule('a.b', RuleType::Wildcard),
            '*.a.b',
        ];
        yield 'exception rule' => [
            new Rule('foo.a.b', RuleType::Exception),
            '!foo.a.b',
        ];
    }

    #[DataProvider('sortOrderProvider')]
    public function testSortOrder(array $rules, array $expected): void
    {
        usort($rules, Rule::compare(...));
        $result = array_map(strval(...), $rules);
        Assert::assertSame($expected, $result);
    }

    public static function sortOrderProvider(): iterable
    {
        yield 'rules are sorted by label from right to left' => [
            [
                new Rule('b'), new Rule('a'),
                new Rule('a.b'), new Rule('a.a'),
            ],
            ['a', 'a.a', 'b', 'a.b'],
        ];
        yield 'sort order uses rule type' => [
            [
                new Rule('a.b', RuleType::Exception),
                new Rule('a.b', RuleType::Wildcard),
                new Rule('a.b'),
            ],
            ['a.b', '*.a.b', '!a.b'],
        ];
        yield 'wildcards sort after defaults' => [
            [new Rule('a.b', RuleType::Wildcard), new Rule('a.b')],
            ['a.b', '*.a.b'],
        ];
        yield 'exceptions sort after wildcards' => [
            [new Rule('a.b', RuleType::Exception), new Rule('a.b', RuleType::Wildcard)],
            ['*.a.b', '!a.b'],
        ];
    }
}
