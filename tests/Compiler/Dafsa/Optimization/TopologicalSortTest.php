<?php declare(strict_types=1);

namespace ju1ius\FusBup\Tests\Compiler\Dafsa\Optimization;

use ju1ius\FusBup\Compiler\Dafsa\Node;
use ju1ius\FusBup\Compiler\Dafsa\Optimization\TopologicalSort;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class TopologicalSortTest extends TestCase
{
    private static function process(array $nodes): array
    {
        return (new TopologicalSort())->process($nodes);
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
        yield 'a DAFSA with no interior nodes can be sorted' => [
            [Node::sink()],
            [],
        ];
        yield 'a DAFSA with one node can be sorted' => [
            // ['a'] => ['a']
            [Node::terminal('a')],
            [Node::terminal('a')],
        ];
    }

    /**
     * Tests that nodes in a diamond can be sorted.
     *   'b'
     *   / \
     * 'a' 'd'
     *   \ /
     *   'c'
     */
    public function testDiamond(): void
    {
        $input = [
            $a = Node::of('a', [
                $b = Node::of('b', [
                    $d = Node::terminal('d'),
                ]),
                $c = Node::of('c', [$d]),
            ]),
        ];
        $result = self::process($input);
        Assert::assertEquals([$a, $c, $b, $d], $result);
        $indexOf = fn($node) => array_search($node, $result, true);
        Assert::assertTrue($indexOf($a) < $indexOf($b));
        Assert::assertTrue($indexOf($b) < $indexOf($d));
        Assert::assertTrue($indexOf($c) < $indexOf($d));
    }
}
