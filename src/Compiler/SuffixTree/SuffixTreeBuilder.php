<?php declare(strict_types=1);

namespace Souplette\FusBup\Compiler\SuffixTree;

use Souplette\FusBup\Compiler\Parser\Rule;
use Souplette\FusBup\Compiler\Parser\RuleList;
use Souplette\FusBup\Compiler\Parser\RuleType;
use Souplette\FusBup\Compiler\Parser\Section;
use Souplette\FusBup\Lookup\SuffixTree;
use Souplette\FusBup\Lookup\SuffixTree\Flags;
use Souplette\FusBup\Lookup\SuffixTree\Node;

/**
 * Builds a compressed suffix tree from an array of parsed `Rule` objects.
 *
 * @internal
 */
final class SuffixTreeBuilder
{
    public static function build(RuleList $rules): SuffixTree
    {
        return self::process(RuleTree::of($rules));
    }

    private static function process(RuleTree $ruleTree): SuffixTree
    {
        $root = self::processNode($ruleTree->root);
        return new SuffixTree($root);
    }

    private static function processNode(RuleNode $node): Node|int
    {
        $rule = $node->value;
        $flags = match ($rule) {
            null => Flags::CONTINUE,
            default => self::processRule($rule),
        };

        if (!$node->children) {
            return $flags;
        }

        $children = [];
        foreach ($node->children as $label => $child) {
            $children[$label] = self::processNode($child);
        }

        return new Node($flags, $children);
    }

    private static function processRule(Rule $rule): int
    {
        $flags = match ($rule->type) {
            RuleType::Default => Flags::STORE,
            RuleType::Wildcard => Flags::WILDCARD,
            RuleType::Exception => Flags::EXCLUDE,
        };
        $flags |= match ($rule->section) {
            Section::Icann => 0,
            default => Flags::PRIVATE,
        };

        return $flags;
    }
}
