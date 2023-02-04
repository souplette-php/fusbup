<?php declare(strict_types=1);

namespace ju1ius\FusBup\Tests\Lookup;

use ju1ius\FusBup\Compiler\DafsaCompiler;
use ju1ius\FusBup\Lookup\Dafsa;

final class DafsaTest extends LookupTestCase
{
    protected static function compile(array $rules): Dafsa
    {
        $dafsa = (new DafsaCompiler())->compile($rules, true);
        return new Dafsa(substr($dafsa, 16));
    }
}
