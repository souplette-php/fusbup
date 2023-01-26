<?php declare(strict_types=1);

namespace ju1ius\FusBup\Suffix;

use ju1ius\FusBup\Parser\Rule;
use ju1ius\FusBup\Parser\RuleType;

final class Tree
{
    public function __construct(
        private readonly Node $root = new Node(),
    ) {
    }


    public static function of(array $rules): self
    {
        $self = new self();
        foreach ($rules as $rule) {
            $self->add($rule);
        }
        return $self;
    }

    /**
     * @link https://github.com/publicsuffix/list/wiki/Format#algorithm
     */
    public function match(string $domain): array
    {
        $labels = array_reverse(explode('.', idn_to_ascii($domain)));
        $node = $this->root;
        $matches = [];
        foreach ($labels as $i => $label) {
            $node = $node->children[$label] ?? null;
            if (!$node) break;
            if ($rule = $node->value) {
                if ($rule->type === RuleType::Wildcard) {
                    if ($next = $labels[$i + 1] ?? null) {
                        $matches[] = "{$next}.{$rule->suffix}";
                    }
                } else if ($rule->type === RuleType::Exception) {
                    // TODO: can we do better?
                    $matches = array_filter($matches, fn($s) => $s !== $rule->suffix);
                } else {
                    $matches[] = $rule->suffix;
                }
            }
        }

        return $matches ?: array_slice($labels, 0, 1);
    }

    private function add(Rule $rule): void
    {
        $node = $this->root;
        foreach ($rule->labels as $label) {
            $node->children[$label] ??= new Node();
            $node = $node->children[$label];
        }
        $node->value = $rule;
    }
}
