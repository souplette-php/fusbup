<?php declare(strict_types=1);

namespace ju1ius\FusBup\Tests\PublicSuffixList;

use ju1ius\FusBup\Loader\DafsaLoader;
use ju1ius\FusBup\PublicSuffixList;

final class PslDafsaTest extends PslTestCase
{
    private static PublicSuffixList $list;

    protected static function getList(): PublicSuffixList
    {
        return self::$list ??= new PublicSuffixList(new DafsaLoader());
    }
}
