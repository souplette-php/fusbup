<?php declare(strict_types=1);

namespace ju1ius\FusBup\Tests\Compiler\Dafsa;

use ju1ius\FusBup\Compiler\Dafsa\Dafsa;
use ju1ius\FusBup\Compiler\Dafsa\Node;
use ju1ius\FusBup\Tests\ResourceHelper;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

/**
 * Ported from:
 * @link https://hg.mozilla.org/mozilla-central/file/tip/xpcom/ds/test/test_dafsa.py
 */
final class MozillaDafsaTest extends TestCase
{
    /**
     * @dataProvider fromWordsProvider
     */
    public function testFromWords(string $input, string $expected): void
    {
        $words = array_map(trim(...), explode("\n", trim($input)));
        $expected = implode(
            "\n",
            array_map(trim(...), explode("\n", trim($expected)))
        );
        $dafsa = Dafsa::of($words);
        $repr = self::reprDafsa($dafsa);
        Assert::assertSame($expected, $repr);
    }

    public static function fromWordsProvider(): iterable
    {
        foreach (ResourceHelper::glob('dafsa/gecko/test_*.txt') as $path) {
            $buffer = file_get_contents($path);
            [$input, $expected] = explode('>>>>>>>>>>', $buffer);
            $name = basename($path, '.txt');
            yield $name => [$input, $expected];
        }
    }

    private static function reprDafsa(Dafsa $dafsa): string
    {
        $buffer = '';
        $cache = new \SplObjectStorage();
        $children = array_values($dafsa->rootNode->children);
        usort($children, fn($a, $b) => $a->char <=> $b->char);
        foreach ($children as $child) {
            self::reprNode($child, '', $buffer, $cache);
        }
        return trim($buffer);
    }

    private static function reprNode(Node $node, string $prefix, string &$buffer, \SplObjectStorage $cache): void
    {
        if (!$node->isSink) {
            $prefix .= $node->char < "\x0A" ? \ord($node->char) : $node->char;
        } else {
            $prefix .= '$';
        }
        $cached = $cache->contains($node) ? $cache[$node] : null;
        $buffer .= trim(sprintf('%s%s', $prefix, $cached ? '=' : '')) . "\n";
        if (!$cached) {
            $cache[$node] = $node;
            $children = array_values($node->children);
            usort($children, fn($a, $b) => $a->char <=> $b->char);
            foreach ($children as $child) {
                self::reprNode($child, $prefix, $buffer, $cache);
            }
        }
    }
}
