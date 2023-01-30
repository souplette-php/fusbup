<?php declare(strict_types=1);

namespace ju1ius\FusBup\Tests\Compiler\Dafsa\TreeBuilder;

use ju1ius\FusBup\Compiler\Dafsa\Node;
use ju1ius\FusBup\Compiler\Dafsa\TreeBuilder\AsciiTreeBuilder;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class AsciiTreeBuilderTest extends TestCase
{
    private static function build(array $words): array
    {
        return (new AsciiTreeBuilder())->build($words);
    }

    public function testWordListCannotBeEmpty(): void
    {
        $this->expectException(\RuntimeException::class);
        self::build([]);
    }

    /**
     * @dataProvider nonAsciiWordsProvider
     */
    public function testNonAsciiWords(string $word): void
    {
        $this->expectException(\RuntimeException::class);
        self::build([$word]);
    }

    public static function nonAsciiWordsProvider(): iterable
    {
        yield ["\x1Fa1"];
        yield ["a\x1F1"];
        yield ["\x80a1"];
        yield ["a\x801"];
    }

    /**
     * @dataProvider buildProvider
     */
    public function testBuild(array $input, array $expected): void
    {
        $result = self::build($input);
        Assert::assertEquals($expected, $result);
    }

    public static function buildProvider(): iterable
    {
        yield 'single char' => [
            ['a0'],
            [Node::of('a', [
                Node::terminal("\x00"),
            ])],
        ];
        yield 'multi-chars' => [
            ['ab0'],
            [Node::of('a', [
                Node::of('b', [
                    Node::terminal("\x00"),
                ])
            ])]
        ];
        yield 'multiple words' => [
            ['a0', 'b1'],
            [
                Node::of('a', [
                    Node::terminal("\x00"),
                ]),
                Node::of('b', [
                    Node::terminal("\x01"),
                ]),
            ]
        ];
    }
}
