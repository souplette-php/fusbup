<?php declare(strict_types=1);

namespace ju1ius\FusBup\Benchmarks;

use ju1ius\FusBup\Loader\DafsaFileLoader;
use ju1ius\FusBup\Loader\PhpFileLoader;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\RetryThreshold;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Subject;

#[RetryThreshold(2.0)]
final class LoaderBench
{
    #[Subject]
    #[Iterations(10)]
    #[Revs(10)]
    public function suffixTree(): void
    {
        $loader = new PhpFileLoader();
        $loader->load();
    }

    #[Subject]
    #[Iterations(10)]
    #[Revs(100)]
    public function dafsa(): void
    {
        $loader = new DafsaFileLoader();
        $loader->load();
    }
}
