<?php declare(strict_types=1);

namespace ju1ius\FusBup\Tests\Lookup;

use ju1ius\FusBup\Compiler\DafsaCompiler;
use ju1ius\FusBup\Lookup\Dafsa;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class DafsaTest extends TestCase
{
    private static function compile(array $rules): Dafsa
    {
        $dafsa = (new DafsaCompiler())->compile($rules, true);
        return new Dafsa(substr($dafsa, 16));
    }

    /**
     * @dataProvider splitProvider
     */
    public function testSplit(array $rules, string $domain, array $expected): void
    {
        $graph = self::compile($rules);
        $result = $graph->split($domain);
        Assert::assertSame($expected, $result);
    }

    public static function splitProvider(): iterable
    {
        yield from PslLookupTestProvider::splitCases();
    }
}
