<?php declare(strict_types=1);

namespace ju1ius\FusBup\Tests\PublicSuffixList;

use ju1ius\FusBup\Loader\DafsaFileLoader;
use ju1ius\FusBup\PublicSuffixList;
use PHPUnit\Framework\Assert;

final class PslDafsaTest extends PslTestCase
{
    private static PublicSuffixList $list;

    protected static function getList(): PublicSuffixList
    {
        return self::$list ??= new PublicSuffixList(new DafsaFileLoader());
    }
}
