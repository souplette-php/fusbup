<?php declare(strict_types=1);

namespace ju1ius\FusBup\Benchmarks\Lookup;

use ju1ius\FusBup\Loader\PhpFileLoader;
use ju1ius\FusBup\Lookup\LookupInterface;

final class SuffixTreeBench extends AbstractLookupBenchmark
{
    protected static function getLookup(): LookupInterface
    {
        return (new PhpFileLoader())->load();
    }
}