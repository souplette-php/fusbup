<?php declare(strict_types=1);

namespace ju1ius\FusBup\Tests\Compiler\Dafsa\Optimization;

use ju1ius\FusBup\Compiler\Dafsa\Node;
use ju1ius\FusBup\Compiler\Dafsa\Optimization\JoinSuffixes;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class JoinSuffixesTest extends TestCase
{
    private static function process(array $nodes): array
    {
        return (new JoinSuffixes())->process($nodes);
    }

    /**
     * @dataProvider processProvider
     */
    public function testProcess(array $nodes, array $expected): void
    {
        $result = self::process($nodes);
        Assert::assertEquals($expected, $result);
    }

    public static function processProvider(): iterable
    {
        yield 'single label is unchanged' => [
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
        yield 'a trie formed DAFSA with distinct labels passes unchanged' => [
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
        yield 'a reverse trie formed DAFSA with distinct labels passes unchanged' => [
            // 'a'        'a'
            //   \          \
            //   'c'  =>    'c'
            //   /          /
            // 'b'        'b'
            $nodes3 = [
                Node::of('a', [
                    $diamond1 = Node::terminal('c'),
                ]),
                Node::of('b', [$diamond1]),
            ],
            $nodes3,
        ];
    }

    /**
     * Two heads can be joined even if there is something else between.
     *
     * 'a'       ------'a'
     *                 /
     * 'b'  =>  'b'   /
     *               /
     * 'a'       ---
     *
     * The picture above should shows that the new version
     * should have just one instance of the node with label 'a'.
     */
    public function testJoinTwoHeads(): void
    {
        $input = [
            Node::terminal('a'),
            Node::terminal('b'),
            Node::terminal('a'),
        ];
        $result = self::process($input);
        // Both versions should expand to the same content.
        Assert::assertEquals($input, $result);
        // But the new version should have just one instance of 'a'.
        Assert::assertSame($result[0], $result[2]);
    }

    /**
     * 'a' -> 'c'      'a'
     *                   \
     *             =>    'c'
     *                   /
     * 'b' -> 'c'      'b'
     */
    public function testJoinTails(): void
    {
        $input = [
            Node::of('a', [
                Node::terminal('c'),
            ]),
            Node::of('b', [
                Node::terminal('c'),
            ]),
        ];
        $result = self::process($input);
        // Both versions should expand to the same content.
        Assert::assertEquals($input, $result);
        // But the new version should have just one tail.
        Assert::assertSame($result[0]->children[0], $result[1]->children[0]);
    }

    /**
     * 'a' -> 'e' -> 'g'     'a'
     *                         \
     *                         'e'
     *                         / \
     * 'b' -> 'e' -> 'g'     'b'  \
     *                             \
     *                   =>        'g'
     *                             /
     * 'c' -> 'f' -> 'g'     'c'  /
     *                         \ /
     *                         'f'
     *                         /
     * 'd' -> 'f' -> 'g'     'd'
     */
    public function testRecursiveSuffixJoin(): void
    {
        $input = [
            Node::of('a', [
                $nodeE = Node::of('e', [
                    $nodeG = Node::terminal('g'),
                ])
            ]),
            Node::of('b', [$nodeE]),
            Node::of('c', [
                $nodeF = Node::of('f', [$nodeG]),
            ]),
            Node::of('d', [$nodeF]),
        ];
        $result = self::process($input);
        // Both versions should expand to the same content.
        Assert::assertEquals($input, $result);
        // But the new version should have just one 'e'.
        Assert::assertSame($result[0]->children[0], $result[1]->children[0]);
        // And one 'f'.
        Assert::assertSame($result[2]->children[0], $result[3]->children[0]);
        // And one 'g'.
        Assert::assertSame(
            $result[0]->children[0]->children[0],
            $result[2]->children[0]->children[0],
        );
    }

    /**
     * We can join suffixes of a trie.
     *
     *   'b' -> 'd'        'b'
     *   /                 / \
     * 'a'           =>  'a' 'd'
     *   \                 \ /
     *   'c' -> 'd'        'c'
     */
    public function testJoinCanCreateDiamonds(): void
    {
        $input = [
            Node::of('a', [
                Node::of('b', [
                    Node::terminal('d'),
                ]),
                Node::of('c', [
                    Node::terminal('d'),
                ]),
            ]),
        ];
        $result = self::process($input);
        // Both versions should expand to the same content.
        Assert::assertEquals($input, $result);
        // But the new version should have just one 'd'.
        Assert::assertSame(
            $result[0]->children[0]->children[0],
            $result[0]->children[1]->children[0],
        );
    }

    /**
     * We can join some children but not all.
     *   'c'            ----'c'
     *   /            /     /
     * 'a'          'a'    /
     *   \            \   /
     *   'd'          'd'/
     *          =>      /
     *   'c'           /
     *   /            /
     * 'b'          'b'
     *   \            \
     *   'e'          'e'
     */
    public function testJoinOneChild(): void
    {
        $input = [
            Node::of('a', [
                Node::terminal('c'),
                Node::terminal('d'),
            ]),
            Node::of('b', [
                Node::terminal('c'),
                Node::terminal('e'),
            ]),
        ];
        $result = self::process($input);
        // Both versions should expand to the same content.
        Assert::assertEquals($input, $result);
        // But the new version should have just one 'c'.
        Assert::assertSame(
            $result[0]->children[0],
            $result[1]->children[0],
        );
    }
}
