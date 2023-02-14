<?php declare(strict_types=1);

namespace Souplette\FusBup\Tests\Lookup;

use Souplette\FusBup\Compiler\DafsaCompiler;
use Souplette\FusBup\Compiler\Parser\RuleList;
use Souplette\FusBup\Lookup\Dafsa;

final class DafsaTest extends LookupTestCase
{
    protected static function compile(array $rules): Dafsa
    {
        $dafsa = (new DafsaCompiler())->compile(RuleList::of($rules), true);
        return new Dafsa(substr($dafsa, 16));
    }
}
