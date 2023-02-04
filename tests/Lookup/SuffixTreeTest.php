<?php declare(strict_types=1);

namespace ju1ius\FusBup\Tests\Lookup;

use ju1ius\FusBup\Compiler\Parser\RuleList;
use ju1ius\FusBup\Compiler\SuffixTree\SuffixTreeBuilder;
use ju1ius\FusBup\Lookup\SuffixTree;

final class SuffixTreeTest extends LookupTestCase
{
    protected static function compile(array $rules): SuffixTree
    {
        return SuffixTreeBuilder::build(RuleList::of($rules));
    }
}
