<?php declare(strict_types=1);

namespace ju1ius\FusBup\Tests\Compiler\Dafsa\Encoder;

use ju1ius\FusBup\Compiler\Dafsa\Encoder\AsciiEncoder;
use ju1ius\FusBup\Compiler\Dafsa\Node;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class AsciiEncoderTest extends TestCase
{
    /**
     * @dataProvider encodeProvider
     */
    public function testEncode(array $nodes, array $expected): void
    {
        $enc = new AsciiEncoder();
        $bytes = $enc->encode($nodes);
        Assert::assertSame($expected, $bytes);
    }

    public static function encodeProvider(): iterable
    {
        yield 'single node with single char label' => [
            [Node::terminal('a')],
            [0x81, \ord('a') + 0x80],
        ];
        yield 'single node with multi char label' => [
            [Node::terminal('ab')],
            [0x81, \ord('a'), \ord('b')+ 0x80],
        ];
        yield 'sequence of single char terminal nodes' => [
            [
                Node::terminal('a'),
                Node::terminal('b'),
            ],
            [0x02, 0x81, \ord('b') + 0x80, \ord('a') + 0x80],
        ];
        yield 'node with single terminal child' => [
            [
                Node::of('a', [
                    Node::terminal('b'),
                ]),
            ],
            [0x81, \ord('a'), \ord('b') + 0x80],
        ];
    }
}
