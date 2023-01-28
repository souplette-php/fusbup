<?php declare(strict_types=1);

namespace ju1ius\FusBup\Compiler;

use ju1ius\FusBup\Parser\Rule;
use ju1ius\FusBup\Parser\RuleType;
use ju1ius\FusBup\SuffixTree\Node;
use ju1ius\FusBup\SuffixTree\Opcodes;
use ju1ius\FusBup\SuffixTree\Tree;

/**
 * Builds a compressed suffix tree from an array of parsed `Rule` objects.
 *
 * @internal
 */
final class SuffixTreeBuilder
{
    public static function build(array $rules): Tree
    {
        return self::process(RuleTree::of($rules));
    }

    private static function process(RuleTree $ruleTree): Tree
    {
        $root = self::processNode($ruleTree->root);
        return new Tree($root);
    }

    private static function processNode(RuleNode $node): Node|int
    {
        $rule = $node->value;
        $opcode = match ($rule) {
            null => Opcodes::CONTINUE,
            default => self::processRule($rule),
        };

        if (!$node->children) {
            return $opcode;
        }

        $children = [];
        foreach ($node->children as $label => $child) {
            $children[$label] = self::processNode($child);
        }

        return new Node($opcode, $children);
    }

    private static function processRule(Rule $rule): int
    {
        return match ($rule->type) {
            RuleType::Default => Opcodes::STORE,
            RuleType::Wildcard => Opcodes::WILDCARD,
            RuleType::Exception => Opcodes::EXCLUDE,
        };
    }
}
