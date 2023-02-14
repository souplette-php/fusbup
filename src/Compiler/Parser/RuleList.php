<?php declare(strict_types=1);

namespace Souplette\FusBup\Compiler\Parser;

use Souplette\FusBup\Exception\ParseError;
use Traversable;

final class RuleList implements \IteratorAggregate
{
    private function __construct(
        /** @var Rule[] */
        private array $rules,
    ) {
    }

    public static function of(array $rules): self
    {
        usort($rules, Rule::compare(...));
        self::validate($rules);
        return new self($rules);
    }

    public function getIterator(): Traversable
    {
        yield from $this->rules;
    }

    /**
     * @param Rule[] $rules
     */
    private static function validate(array $rules): void
    {
        $ruleSet = [];
        foreach ($rules as $rule) {
            $suffix = implode('.', array_reverse($rule->labels));
            if (isset($ruleSet[$rule->type->value][$suffix])) {
                throw ParseError::duplicateRule($rule);
            }
            $ruleSet[$rule->type->value][$suffix] = $rule;
            if ($rule->type === RuleType::Exception) {
                // parser enforces that exception rules always have at least two labels
                $wildcard = substr($suffix, strpos($suffix, '.') + 1);
                if (!isset($ruleSet[RuleType::Wildcard->value][$wildcard])) {
                    throw ParseError::exceptionRuleWithoutMatchingWildcard($rule);
                }
            }
        }
    }
}
