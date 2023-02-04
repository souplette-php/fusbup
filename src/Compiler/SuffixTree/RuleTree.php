<?php declare(strict_types=1);

namespace ju1ius\FusBup\Compiler\SuffixTree;

use ju1ius\FusBup\Compiler\Parser\Rule;
use ju1ius\FusBup\Compiler\Parser\RuleList;

/**
 * @internal
 */
final class RuleTree
{
    public function __construct(
        public readonly RuleNode $root = new RuleNode(),
    ) {
    }

    public static function of(RuleList $rules): self
    {
        $self = new self();
        foreach ($rules as $rule) {
            $self->add($rule);
        }
        return $self;
    }

    private function add(Rule $rule): void
    {
        $node = $this->root;
        foreach ($rule->labels as $label) {
            $node->children[$label] ??= new RuleNode();
            $node = $node->children[$label];
        }
        $node->value = $rule;
    }
}
