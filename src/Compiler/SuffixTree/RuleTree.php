<?php declare(strict_types=1);

namespace ju1ius\FusBup\Compiler\SuffixTree;

use ju1ius\FusBup\Parser\Rule;

/**
 * @internal
 */
final class RuleTree
{
    public function __construct(
        public readonly RuleNode $root = new RuleNode(),
    ) {
    }

    /**
     * @param Rule[] $rules
     */
    public static function of(array $rules): self
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
