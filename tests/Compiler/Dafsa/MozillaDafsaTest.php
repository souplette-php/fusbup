<?php declare(strict_types=1);

namespace ju1ius\FusBup\Tests\Compiler\Dafsa;

use ju1ius\FusBup\Compiler\Dafsa\Dafsa;
use ju1ius\FusBup\Compiler\Dafsa\Node;
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
        yield 'test_1' => [
            <<<'EOS'
            a1
            ac1
            acc1
            bd1
            bc1
            bcc1
            EOS,
            <<<'EOS'
            a
            a1
            a1$
            ac
            ac1=
            acc
            acc1=
            b
            bc=
            bd
            bd1=
            EOS,
        ];
        yield 'test_2' => [
            <<<'EOS'
            ab1
            b1
            bb1
            bbb1
            EOS,
            <<<'EOS'
            a
            ab
            ab1
            ab1$
            b
            b1=
            bb
            bb1=
            bbb=
            EOS,
        ];
        yield 'test_3' => [
            <<<'EOS'
            a.ca1
            a.com1
            c.corg1
            b.ca1
            b.com1
            b.corg1
            EOS,
            <<<'EOS'
            a
            a.
            a.c
            a.ca
            a.ca1
            a.ca1$
            a.co
            a.com
            a.com1=
            b
            b.
            b.c
            b.ca=
            b.co
            b.com=
            b.cor
            b.corg
            b.corg1=
            c
            c.
            c.c
            c.co
            c.cor=
            EOS,
        ];
        yield 'test_4' => [
            <<<'EOS'
            acom1
            bcomcom1
            acomcom1
            EOS,
            <<<'EOS'
            a
            ac
            aco
            acom
            acom1
            acom1$
            acomc
            acomco
            acomcom
            acomcom1=
            b
            bc
            bco
            bcom
            bcomc=
            EOS,
        ];
        yield 'test_5' => [
            <<<'EOS'
            a.d1
            a.c.d1
            b.d1
            b.c.d1
            EOS,
            <<<'EOS'
            a
            a.
            a.c
            a.c.
            a.c.d
            a.c.d1
            a.c.d1$
            a.d=
            b
            b.=
            EOS,
        ];
        yield 'test_6' => [
            <<<'EOS'
            a61
            a661
            b61
            b661
            EOS,
            <<<'EOS'
            a
            a6
            a61
            a61$
            a66
            a661=
            b
            b6=
            EOS,
        ];
        yield 'test_7' => [
            <<<'EOS'
            a61
            a6661
            b61
            b6661
            EOS,
            <<<'EOS'
            a
            a6
            a61
            a61$
            a66
            a666
            a6661=
            b
            b6=
            EOS,
        ];
        yield 'test_8' => [
            <<<'EOS'
            acc1
            bc1
            bccc1
            EOS,
            <<<'EOS'
            a
            ac
            acc
            acc1
            acc1$
            b
            bc
            bc1=
            bcc=
            EOS,
        ];
        yield 'test_9' => [
            <<<'EOS'
            acc1
            bc1
            bcc1
            EOS,
            <<<'EOS'
            a
            ac
            acc
            acc1
            acc1$
            b
            bc
            bc1=
            bcc=
            EOS,
        ];
        yield 'test_10' => [
            <<<'EOS'
            acc1
            cc1
            cccc1
            EOS,
            <<<'EOS'
            a
            ac
            acc
            acc1
            acc1$
            c
            cc
            cc1=
            ccc=
            EOS,
        ];
        yield 'test_11' => [
            <<<'EOS'
            ac1
            acc1
            bc1
            bcc1
            EOS,
            <<<'EOS'
            a
            ac
            ac1
            ac1$
            acc
            acc1=
            b
            bc=
            EOS,
        ];
        yield 'test_12' => [
            <<<'EOS'
            acd1
            bcd1
            bcdd1
            EOS,
            <<<'EOS'
            a
            ac
            acd
            acd1
            acd1$
            b
            bc
            bcd
            bcd1=
            bcdd=
            EOS,
        ];
        yield 'test_13' => [
            <<<'EOS'
            ac1
            acc1
            bc1
            bcc1
            bccc1
            EOS,
            <<<'EOS'
            a
            ac
            ac1
            ac1$
            acc
            acc1=
            b
            bc
            bc1=
            bcc=
            EOS,
        ];
        yield 'test_14' => [
            <<<'EOS'
            acc1
            acccc1
            bcc1
            bcccc1
            bcccccc1
            EOS,
            <<<'EOS'
            a
            ac
            acc
            acc1
            acc1$
            accc
            acccc
            acccc1=
            b
            bc
            bcc
            bcc1=
            bccc=
            EOS,
        ];
        yield 'test_15' => [
            <<<'EOS'
            ac1
            bc1
            acac1
            EOS,
            <<<'EOS'
            a
            ac
            ac1
            ac1$
            aca
            acac
            acac1=
            b
            bc=
            EOS,
        ];
        yield 'test_16' => [
            <<<'EOS'
            bat1
            t1
            tbat1
            EOS,
            <<<'EOS'
            b
            ba
            bat
            bat1
            bat1$
            t
            t1=
            tb=
            EOS,
        ];
        yield 'test_17' => [
            <<<'EOS'
            acow1
            acat1
            t1
            tcat1
            acatcat1
            EOS,
            <<<'EOS'
            a
            ac
            aca
            acat
            acat1
            acat1$
            acatc
            acatca
            acatcat
            acatcat1=
            aco
            acow
            acow1=
            t=
            EOS,
        ];
        yield 'test_18' => [
            <<<'EOS'
            bc1
            abc1
            abcxyzc1
            EOS,
            <<<'EOS'
            a
            ab
            abc
            abc1
            abc1$
            abcx
            abcxy
            abcxyz
            abcxyzc
            abcxyzc1=
            b
            bc=
            EOS,
        ];
        yield 'test_19' => [
            <<<'EOS'
            a.z1
            a.y1
            c.z1
            d.z1
            d.y1
            EOS,
            <<<'EOS'
            a
            a.
            a.y
            a.y1
            a.y1$
            a.z
            a.z1=
            c
            c.
            c.z=
            d
            d.=
            EOS,
        ];
        yield 'test_20' => [
            <<<'EOS'
            acz1
            acy1
            accz1
            acccz1
            bcz1
            bcy1
            bccz1
            bcccz1
            EOS,
            <<<'EOS'
            a
            ac
            acc
            accc
            acccz
            acccz1
            acccz1$
            accz=
            acy
            acy1=
            acz=
            b
            bc=
            EOS,
        ];
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
