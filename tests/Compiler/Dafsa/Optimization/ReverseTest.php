<?php declare(strict_types=1);

namespace ju1ius\FusBup\Tests\Compiler\Dafsa\Optimization;

use ju1ius\FusBup\Compiler\Dafsa\Node;
use ju1ius\FusBup\Compiler\Dafsa\Optimization\Reverse;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class ReverseTest extends TestCase
{
    public static function process(array $nodes): array
    {
        return (new Reverse())->process($nodes);
    }

    /**
     * @dataProvider processProvider
     */
    public function testProcess(array $input, array $expected): void
    {
        $result = self::process($input);
        Assert::assertEquals($expected, $result);
    }

    public static function processProvider(): iterable
    {
        yield 'an atomic label passes unchanged' => [
            // 'a' => 'a'
            [Node::terminal('a')],
            [Node::terminal('a')],
        ];
        yield 'labels are reversed' => [
            // 'ab'  =>  'ba'
            [Node::terminal('ab')],
            [Node::terminal('ba')],
        ];
        yield 'edges are reversed' => [
            // 'a' -> 'b'  =>  'b' -> 'a'
            [Node::of('a', [
                Node::terminal('b'),
            ])],
            [Node::of('b', [
                Node::terminal('a'),
            ])],
        ];
        yield 'a sequence with an inner terminator can be reversed' => [
            // 'a' -> 'b'    'b' -> 'a'
            //   \       =>         /
            //  {sink}        ------
            [
                Node::of('a', [
                    Node::terminal('b'),
                    Node::sink(),
                ]),
            ],
            [
                Node::of('b', [
                    Node::terminal('a'),
                ]),
                Node::terminal('a'),
            ],
        ];
        yield 'a trie formed DAFSA can be reversed' => [
            //   'b'     'b'
            //   /         \
            // 'a'   =>    'a'
            //   \         /
            //   'c'     'c'
            [
                Node::of('a', [
                    Node::terminal('b'),
                    Node::terminal('c'),
                ]),
            ],
            [
                Node::of('b', [Node::terminal('a')]),
                Node::of('c', [Node::terminal('a')]),
            ],
        ];
        yield 'a reverse trie formed DAFSA can be reversed' => [
            // 'a'          'a'
            //   \          /
            //   'c'  =>  'c'
            //   /          \
            // 'b'          'b'
            [
                Node::of('a', [$nodeC1 = Node::terminal('c')]),
                Node::of('b', [$nodeC1]),
            ],
            [
                Node::of('c', [
                    Node::terminal('a'),
                    Node::terminal('b'),
                ])
            ],
        ];
    }

    /**
     * Tests that we can reverse both edges and nodes in a diamond
     *   'cd'           'dc'
     *   /  \           /  \
     * 'ab' 'gh'  =>  'hg' 'ba'
     *   \  /           \  /
     *   'ef'           'fe'
     */
    public function testDiamond(): void
    {
        $input = [
            Node::of('ab', [
                Node::of('cd', [
                    $nodeGH1 = Node::terminal('gh'),
                ]),
                Node::of('ef', [$nodeGH1]),
            ])
        ];
        $expected = [
            Node::of('hg', [
                Node::of('dc', [Node::terminal('ba')]),
                Node::of('fe', [Node::terminal('ba')]),
            ]),
        ];
        $result = self::process($input);
        Assert::assertEquals($expected, $result);
        Assert::assertSame(
            $result[0]->children[0]->children[0],
            $result[0]->children[1]->children[0],
        );
    }
}
