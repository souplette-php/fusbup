<?php declare(strict_types=1);

namespace ju1ius\FusBup\Compiler;

use ju1ius\FusBup\Parser\Rule;
use ju1ius\FusBup\Parser\RuleType;
use ju1ius\FusBup\Suffix\Node;

final class PslCompiler
{
    /**
     * @param Rule[] $rules
     */
    public function compileToString(array $rules): string
    {
        $tree = RuleTree::of($rules);
        $code = new CodeBuilder();

        $this->compileNode($tree->root, $code);

        return (string)$code;
    }

    /**
     * @param Rule[] $rules
     */
    public function compileToFile(array $rules): string
    {
        $tree = RuleTree::of($rules);
        $code = CodeBuilder::forFile()->raw('return ');
        $this->compileNode($tree->root, $code);
        $code->raw(";\n");

        return (string)$code;
    }

    private function compileNode(RuleNode $node, CodeBuilder $code): void
    {
        $code->new(Node::class)->raw('(');
        if ($rule = $node->value) {
            $this->compileRule($rule, $code);
        } else {
            $code->repr(null);
        }
        if ($children = $node->children) {
            $code
                ->raw(", [\n")
                ->indent()
                ->each($children, function(RuleNode $v, $k, CodeBuilder $code) {
                    $code->write('')->repr((string)$k)->raw(' => ');
                    $this->compileNode($v, $code);
                    $code->raw(",\n");
                })
                ->dedent()
                ->write(']')
            ;
        }
        $code->raw(')');
    }

    private function compileRule(Rule $rule, CodeBuilder $code): void
    {
        $code
            ->new(Rule::class)->raw('(')
            ->string($rule->suffix)
        ;

        if ($rule->type !== RuleType::Default) {
            $code->raw(', ')->enumCase($rule->type);
        }

        $code->raw(')');
    }
}
