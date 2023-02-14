<?php declare(strict_types=1);

namespace Souplette\FusBup\Tests\PublicSuffixList;

use Souplette\FusBup\Loader\DafsaLoader;
use Souplette\FusBup\PublicSuffixList;

final class PslDafsaTest extends PslTestCase
{
    private static PublicSuffixList $list;

    protected static function getList(): PublicSuffixList
    {
        return self::$list ??= new PublicSuffixList(new DafsaLoader());
    }
}
