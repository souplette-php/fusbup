<?php declare(strict_types=1);

namespace Souplette\FusBup\Compiler\SuffixTree;

use Souplette\FusBup\Compiler\Parser\Rule;

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
