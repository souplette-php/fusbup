<?php declare(strict_types=1);

namespace ju1ius\FusBup\Tests\Lookup;

use ju1ius\FusBup\Compiler\SuffixTree\SuffixTreeBuilder;
use ju1ius\FusBup\Lookup\SuffixTree;

final class SuffixTreeTest extends AbstractLookupTest
{
    protected static function compile(array $rules): SuffixTree
    {
        return SuffixTreeBuilder::build($rules);
    }
}
