<?php declare(strict_types=1);

namespace ju1ius\FusBup\Tests\Compiler\Dafsa\Optimization;

use ju1ius\FusBup\Compiler\Dafsa\Node;
use ju1ius\FusBup\Compiler\Dafsa\Optimization\JoinLabels;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class JoinLabelsTest extends TestCase
{
    private static function process(array $nodes): array
    {
        return (new JoinLabels())->process($nodes);
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
        yield 'a single label passes unchanged' => [
            // 'a'  =>  'a'
            [Node::terminal('a')],
            [Node::terminal('a')],
        ];
        yield 'a sequence with an inner terminator passes unchanged' => [
            // 'a' -> 'b'    'a' -> 'b'
            //   \       =>    \
            //  {sink}        {sink}
            $nodes1 = [Node::of('a', [
                Node::terminal('b'),
                Node::sink(),
            ])],
            $nodes1,
        ];
        yield 'a sequence of labels can be joined' => [
            // 'a' -> 'b'  =>  'ab'
            [Node::of('a', [Node::terminal('b')])],
            [Node::terminal('ab')],
        ];
        yield 'a sequence of multi character labels can be joined' => [
            // 'ab' -> 'cd'  =>  'abcd'
            [Node::of('ab', [Node::terminal('cd')])],
            [Node::terminal('abcd')],
        ];
        yield 'a trie formed DAFSA with atomic labels passes unchanged' => [
            //   'b'       'b'
            //   /         /
            // 'a'   =>  'a'
            //   \         \
            //   'c'       'c'
            $nodes2 = [Node::of('a', [
                Node::terminal('b'),
                Node::terminal('c'),
            ])],
            $nodes2,
        ];
        yield 'a trie formed DAFSA with chained labels can be joined' => [
            //          'c' -> 'd'         'cd'
            //          /                  /
            // 'a' -> 'b'           =>  'ab'
            //          \                  \
            //          'e' -> 'f'         'ef'
            [Node::of('a', [
                Node::of('b', [
                    Node::of('c', [Node::terminal('d')]),
                    Node::of('e', [Node::terminal('f')]),
                ]),
            ])],
            [Node::of('ab', [
                Node::terminal('cd'),
                Node::terminal('ef'),
            ])],
        ];
    }

    /**
     * Tests that a reverse trie formed DAFSA with atomic labels passes unchanged.
     * 'a'        'a'
     *   \          \
     *   'c'  =>    'c'
     *   /          /
     * 'b'        'b'
     */
    public function testReverseAtomicTrie(): void
    {
        $input = [
            Node::of('a', [
                $nodeC1 = Node::terminal('c'),
            ]),
            Node::of('b', [$nodeC1]),
        ];
        $result = self::process($input);
        Assert::assertEquals($input, $result);
        Assert::assertSame(
            $result[0]->children[0],
            $result[1]->children[0],
        );
    }

    /**
     * Tests that a reverse trie formed DAFSA with chained labels can be joined.
     * 'a' -> 'b'               'ab'
     *          \                  \
     *          'e' -> 'f'  =>     'ef'
     *          /                  /
     * 'c' -> 'd'               'cd'
     */
    public function testReversedChainedTrie(): void
    {
        $input = [
            Node::of('a', [
                Node::of('b', [
                    $nodeE1 = Node::of('e', [
                        Node::terminal('f'),
                    ]),
                ]),
            ]),
            Node::of('c', [
                Node::of('d', [$nodeE1]),
            ]),
        ];
        $expected = [
            Node::of('ab', [
                Node::terminal('ef'),
            ]),
            Node::of('cd', [
                Node::terminal('ef'),
            ]),
        ];
        $result = self::process($input);
        Assert::assertEquals($expected, $result);
        Assert::assertSame(
            $result[0]->children[0],
            $result[1]->children[0],
        );
    }
}
