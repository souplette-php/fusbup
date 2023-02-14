<?php declare(strict_types=1);

namespace Souplette\FusBup\Tests\Lookup;

use Souplette\FusBup\Compiler\Parser\RuleList;
use Souplette\FusBup\Compiler\SuffixTree\SuffixTreeBuilder;
use Souplette\FusBup\Lookup\SuffixTree;

final class SuffixTreeTest extends LookupTestCase
{
    protected static function compile(array $rules): SuffixTree
    {
        return SuffixTreeBuilder::build(RuleList::of($rules));
    }
}
