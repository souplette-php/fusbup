<?php declare(strict_types=1);

namespace Souplette\FusBup\Benchmarks\Lookup;

use Souplette\FusBup\Loader\DafsaLoader;
use Souplette\FusBup\Lookup\LookupInterface;

final class DafsaBench extends AbstractLookupBenchmark
{
    protected static function getLookup(): LookupInterface
    {
        return (new DafsaLoader())->load();
    }
}
