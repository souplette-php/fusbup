<?php declare(strict_types=1);

namespace ju1ius\FusBup\Tests;

use ju1ius\FusBup\Loader\PhpFileLoader;
use ju1ius\FusBup\PublicSuffixList;
use PHPUnit\Framework\Assert;

final class PslTreeTest extends AbstractPslTest
{
    private static PublicSuffixList $list;

    protected static function getList(): PublicSuffixList
    {
        return self::$list ??= new PublicSuffixList(new PhpFileLoader());
    }

    /**
     * @dataProvider isPublicSuffixProvider
     */
    public function testIsPublicSuffix(string $input, bool $expected): void
    {
        $result = self::getList()->isPublicSuffix($input);
        Assert::assertSame($expected, $result);
    }

    /**
     * @dataProvider getPublicSuffixProvider
     */
    public function testGetPublicSuffix(string $input, string $expected): void
    {
        $result = self::getList()->getPublicSuffix($input);
        Assert::assertSame($expected, $result);
    }

    /**
     * @dataProvider getRegistrableDomainProvider
     */
    public function testGetRegistrableDomain(string $input, ?string $expected): void
    {
        $result = self::getList()->getRegistrableDomain($input);
        Assert::assertSame($expected, $result);
    }
}
