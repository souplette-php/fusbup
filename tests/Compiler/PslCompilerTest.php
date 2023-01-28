<?php declare(strict_types=1);

namespace ju1ius\FusBup\Tests\Compiler;

use ju1ius\FusBup\Compiler\PslCompiler;
use ju1ius\FusBup\Parser\Rule;
use ju1ius\FusBup\Parser\RuleType;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class PslCompilerTest extends TestCase
{
    /**
     * @dataProvider compileProvider
     */
    public function testCompile(array $rules, string $expected): void
    {
        $code = (new PslCompiler())->compile($rules);
        Assert::assertSame($expected, trim($code));
    }

    public static function compileProvider(): iterable
    {
        yield 'single rule' => [
            [new Rule('a.b')],
            <<<'EOS'
            <?php declare(strict_types=1);

            use ju1ius\FusBup\SuffixTree\Node;

            return new Node(1, [
                'b' => new Node(1, [
                    'a' => 2,
                ]),
            ]);
            EOS,
        ];
        yield 'wildcard rule' => [
            [new Rule('a.b', RuleType::Wildcard)],
            <<<'EOS'
            <?php declare(strict_types=1);

            use ju1ius\FusBup\SuffixTree\Node;

            return new Node(1, [
                'b' => new Node(1, [
                    'a' => 3,
                ]),
            ]);
            EOS,
        ];
        yield 'exception rule' => [
            [new Rule('a.b', RuleType::Exception)],
            <<<'EOS'
            <?php declare(strict_types=1);

            use ju1ius\FusBup\SuffixTree\Node;

            return new Node(1, [
                'b' => new Node(1, [
                    'a' => 4,
                ]),
            ]);
            EOS,
        ];
    }
}
