<?php declare(strict_types=1);

namespace ju1ius\FusBup\Tests\PublicSuffixList;

use ju1ius\FusBup\Loader\SuffixTreeLoader;
use ju1ius\FusBup\PublicSuffixList;

final class PslSuffixTreeTest extends PslTestCase
{
    private static PublicSuffixList $list;

    protected static function getList(): PublicSuffixList
    {
        return self::$list ??= new PublicSuffixList(new SuffixTreeLoader());
    }
}
