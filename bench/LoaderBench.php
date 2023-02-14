<?php declare(strict_types=1);

namespace Souplette\FusBup\Benchmarks;

use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\RetryThreshold;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Subject;
use Souplette\FusBup\Loader\DafsaLoader;
use Souplette\FusBup\Loader\SuffixTreeLoader;

#[RetryThreshold(2.0)]
final class LoaderBench
{
    #[Subject]
    #[Iterations(10)]
    #[Revs(10)]
    public function suffixTree(): void
    {
        $loader = new SuffixTreeLoader();
        $loader->load();
    }

    #[Subject]
    #[Iterations(10)]
    #[Revs(100)]
    public function dafsa(): void
    {
        $loader = new DafsaLoader();
        $loader->load();
    }
}
