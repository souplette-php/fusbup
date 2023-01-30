<?php declare(strict_types=1);

namespace ju1ius\FusBup\Compiler\SuffixTree;

use ju1ius\FusBup\Parser\Rule;

/**
 * @internal
 */
final class RuleNode
{
    /**
     * @var array<string, self>
     */
    public array $children = [];
    public ?Rule $value = null;
}
