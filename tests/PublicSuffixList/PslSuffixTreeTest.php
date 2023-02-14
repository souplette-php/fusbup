<?php declare(strict_types=1);

namespace Souplette\FusBup\Tests\PublicSuffixList;

use Souplette\FusBup\Loader\SuffixTreeLoader;
use Souplette\FusBup\PublicSuffixList;

final class PslSuffixTreeTest extends PslTestCase
{
    private static PublicSuffixList $list;

    protected static function getList(): PublicSuffixList
    {
        return self::$list ??= new PublicSuffixList(new SuffixTreeLoader());
    }
}
