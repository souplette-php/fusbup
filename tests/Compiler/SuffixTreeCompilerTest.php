<?php declare(strict_types=1);

namespace ju1ius\FusBup\Tests\Compiler;

use ju1ius\FusBup\Compiler\Parser\Rule;
use ju1ius\FusBup\Compiler\Parser\RuleType;
use ju1ius\FusBup\Compiler\SuffixTreeCompiler;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SuffixTreeCompilerTest extends TestCase
{
    #[DataProvider('compileProvider')]
    public function testCompile(array $rules, string $expected): void
    {
        $code = (new SuffixTreeCompiler())->compile($rules);
        Assert::assertSame($expected, trim($code));
    }

    public static function compileProvider(): iterable
    {
        yield 'single rule (public)' => [
            [Rule::pub('a.b')],
            <<<'EOS'
            <?php declare(strict_types=1);

            use ju1ius\FusBup\Lookup\SuffixTree\Node;

            return new Node(0, [
                'b' => new Node(0, [
                    'a' => 1,
                ]),
            ]);
            EOS,
        ];
        yield 'single rule (private)' => [
            [new Rule('a.b')],
            <<<'EOS'
            <?php declare(strict_types=1);

            use ju1ius\FusBup\Lookup\SuffixTree\Node;

            return new Node(0, [
                'b' => new Node(0, [
                    'a' => 9,
                ]),
            ]);
            EOS,
        ];
        yield 'wildcard rule (public)' => [
            [Rule::pub('a.b', RuleType::Wildcard)],
            <<<'EOS'
            <?php declare(strict_types=1);

            use ju1ius\FusBup\Lookup\SuffixTree\Node;

            return new Node(0, [
                'b' => new Node(0, [
                    'a' => 2,
                ]),
            ]);
            EOS,
        ];
        yield 'exception rule (public)' => [
            [Rule::pub('a.b', RuleType::Exception)],
            <<<'EOS'
            <?php declare(strict_types=1);

            use ju1ius\FusBup\Lookup\SuffixTree\Node;

            return new Node(0, [
                'b' => new Node(0, [
                    'a' => 4,
                ]),
            ]);
            EOS,
        ];
    }
}
