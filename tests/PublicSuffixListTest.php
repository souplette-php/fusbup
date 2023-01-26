<?php declare(strict_types=1);

namespace ju1ius\FusBup\Tests;

use ju1ius\FusBup\PublicSuffixList;
use ju1ius\FusBup\Utils\Idn;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class PublicSuffixListTest extends TestCase
{
    private static PublicSuffixList $list;

    private static function getList(): PublicSuffixList
    {
        return self::$list ??= new PublicSuffixList();
    }

    /**
     * @dataProvider getTopLevelDomainProvider
     */
    public function testGetTopLevelDomain(string $input, string $expected): void
    {
        $result = self::getList()->getPublicSuffix($input);
        Assert::assertSame($expected, $result);
    }

    public static function getTopLevelDomainProvider(): iterable
    {
        $i = 0;
        foreach (PslTestFile::unregisterable() as [$input, $expected]) {
            // filter out invalid input and expected errors
            if (\is_null($input) || \is_null($expected) || str_starts_with($input, '.')) {
                continue;
            }
            $i++;
            // libpsl returns results in their original form,
            // but we return them in canonicalized unicode form.
            $expected = Idn::toUnicode($expected);
            $key = "#{$i} {$input} => {$expected}";
            yield $key => [$input, $expected];
        }
    }
}
