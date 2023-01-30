<?php declare(strict_types=1);

namespace ju1ius\FusBup\Tests\Parser;

use ju1ius\FusBup\Exception\ParseError;
use ju1ius\FusBup\Parser\PslParser;
use ju1ius\FusBup\Parser\Rule;
use ju1ius\FusBup\Parser\RuleType;
use ju1ius\FusBup\Parser\Section;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class PslParserTest extends TestCase
{
    private static function parse(string|\SplFileObject $input): array
    {
        $parser = new PslParser();
        return $parser->parse($input);
    }

    /**
     * @dataProvider parseStringProvider
     */
    public function testParseString(string $input, array $expected): void
    {
        $rules = self::parse($input);
        Assert::assertEquals($expected, $rules);
    }

    public static function parseStringProvider(): iterable
    {
        yield 'ignores empty lines' => [
            "\n   \n   \n",
            [],
        ];
        yield 'ignores comments' => [
            "// nope\n// nada\n// zilch",
            [],
        ];
        yield 'parses simple rules' => [
            "a.b\nfoo.bar.baz\n",
            [
                new Rule('a.b'),
                new Rule('foo.bar.baz'),
            ]
        ];
        yield 'parses exclusion rules' => [
            "com\n!foo.com",
            [
                new Rule('com'),
                new Rule('foo.com', RuleType::Exception),
            ]
        ];
        yield 'parses wildcard rules' => [
            "*.x.y",
            [
                new Rule('x.y', RuleType::Wildcard),
            ]
        ];
        yield 'handles ICANN & PRIVATE section' => [
            <<<'EOS'
            a
            // ===BEGIN ICANN DOMAINS===
            pub
            // ===END ICANN DOMAINS===
            b
            // ===BEGIN PRIVATE DOMAINS===
            priv
            // ===END PRIVATE DOMAINS===
            // ===BEGIN FOO DOMAINS===
            foo
            EOS,
            [
                new Rule('a'),
                new Rule('pub', section: Section::Icann),
                new Rule('b'),
                new Rule('priv', section: Section::Private),
                new Rule('foo', section: Section::Unknown),
            ],
        ];
    }

    /**
     * @dataProvider parseErrorsProvider
     */
    public function testParseErrors(string|\SplFileObject $input): void
    {
        $this->expectException(ParseError::class);
        self::parse($input);
    }

    public static function parseErrorsProvider(): iterable
    {
        yield 'gibberish' => ["a bunch of nonsense"];
        yield 'mixed rule prefix' => ['!*.x.y'];
        yield 'invalid domain (idn_to_ascii error)' => ['...'];
    }
}
