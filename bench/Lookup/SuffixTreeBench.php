<?php declare(strict_types=1);

namespace Souplette\FusBup\Benchmarks\Lookup;

use Souplette\FusBup\Loader\SuffixTreeLoader;
use Souplette\FusBup\Lookup\LookupInterface;

final class SuffixTreeBench extends AbstractLookupBenchmark
{
    protected static function getLookup(): LookupInterface
    {
        return (new SuffixTreeLoader())->load();
    }
}
