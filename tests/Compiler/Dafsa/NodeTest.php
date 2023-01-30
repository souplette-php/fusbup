<?php declare(strict_types=1);

namespace ju1ius\FusBup\Tests\Compiler\Dafsa;

use ju1ius\FusBup\Compiler\Dafsa\Node;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class NodeTest extends TestCase
{
    /**
     * @dataProvider toWordsProvider
     */
    public function testToWords(Node $input, array $expected): void
    {
        $words = $input->toWords();
        Assert::assertSame($expected, $words);
    }

    public static function toWordsProvider(): iterable
    {
        yield 'sink node' => [
            Node::sink(),
            [''],
        ];
        yield 'single node' => [
            Node::terminal('ab'),
            ['ab'],
        ];
        yield 'node sequence' => [
            Node::of('ab', [
                Node::terminal('cd'),
            ]),
            ['abcd'],
        ];
        yield 'a sequence with an inner terminator is expanded to two strings' => [
            // 'a' -> 'b'
            //   \       => [ 'ab', 'a' ]
            //  {sink}
            Node::of('a', [
                Node::terminal('b'),
                Node::sink(),
            ]),
            ['ab', 'a'],
        ];
        yield 'a diamond can be expanded to a word list' => [
            //   'cd'
            //   /  \
            // 'ab' 'gh'
            //   \  /
            //   'ef'
            Node::of('ab', [
                Node::of('cd', [
                    $diamondNode = Node::terminal('gh'),
                ]),
                Node::of('ef', [$diamondNode]),
            ]),
            ['abcdgh', 'abefgh'],
        ];
    }
}
