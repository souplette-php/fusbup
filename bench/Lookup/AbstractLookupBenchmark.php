<?php declare(strict_types=1);

namespace Souplette\FusBup\Benchmarks\Lookup;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\OutputMode;
use PhpBench\Attributes\OutputTimeUnit;
use PhpBench\Attributes\ParamProviders;
use PhpBench\Attributes\RetryThreshold;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Subject;
use PhpBench\Attributes\Warmup;
use Souplette\FusBup\Lookup\LookupInterface;

#[RetryThreshold(2.0)]
#[Warmup(2)]
#[OutputTimeUnit('seconds')]
#[OutputMode('throughput')]
#[BeforeMethods('setUp')]
abstract class AbstractLookupBenchmark
{
    private LookupInterface $lookup;

    abstract protected static function getLookup(): LookupInterface;

    public function setUp(): void
    {
        $this->lookup = static::getLookup();
    }

    #[Subject]
    #[Revs(1000)]
    #[Iterations(10)]
    #[ParamProviders(['provideDomains'])]
    public function is(array $args): void
    {
        $this->lookup->isEffectiveTLD($args['domain']);
    }

    #[Subject]
    #[Revs(1000)]
    #[Iterations(10)]
    #[ParamProviders(['provideDomains'])]
    public function get(array $args): void
    {
        $this->lookup->getEffectiveTLD($args['domain']);
    }

    #[Subject]
    #[Revs(1000)]
    #[Iterations(10)]
    #[ParamProviders(['provideDomains'])]
    public function split(array $args): void
    {
        $this->lookup->split($args['domain']);
    }

    public static function provideDomains(): iterable
    {
        yield 'existing tld' => ['domain' => 'com'];
        yield 'existing etld' => ['domain' => 'a.b.c.com'];
        yield 'non-existing tld' => ['domain' => 'test'];
        yield 'non-existing etld' => ['domain' => 'a.b.c.test'];
    }
}
