<?php declare(strict_types=1);

namespace Souplette\FusBup\Tests\Compiler;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Souplette\FusBup\Compiler\Exception\ParseError;
use Souplette\FusBup\Compiler\Parser\Rule;
use Souplette\FusBup\Compiler\Parser\RuleList;
use Souplette\FusBup\Compiler\Parser\RuleType;
use Souplette\FusBup\Compiler\Parser\Section;
use Souplette\FusBup\Compiler\PslParser;

final class PslParserTest extends TestCase
{
    private static function parse(string|\SplFileObject $input): RuleList
    {
        $parser = new PslParser();
        return $parser->parse($input);
    }

    #[DataProvider('parseStringProvider')]
    public function testParseString(string $input, array $expected): void
    {
        $rules = self::parse($input);
        Assert::assertEquals(RuleList::of($expected), $rules);
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
        yield 'parses wildcard rules' => [
            "*.x.y",
            [
                new Rule('x.y', RuleType::Wildcard),
            ]
        ];
        yield 'parses exclusion rules' => [
            "*.com\n!foo.com",
            [
                new Rule('com', RuleType::Wildcard),
                new Rule('foo.com', RuleType::Exception),
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

    #[DataProvider('parseErrorsProvider')]
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
        yield 'TLD exception' => ['!com'];
        yield 'exception without matching wildcard' => ["com\n!foo.com"];
        yield 'duplicate rule' => ["a.b\na.b"];
    }
}
