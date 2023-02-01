<?php declare(strict_types=1);

namespace ju1ius\FusBup\Compiler;

use ju1ius\FusBup\Compiler\SuffixTree\SuffixTreeBuilder;
use ju1ius\FusBup\Compiler\Utils\CodeBuilder;
use ju1ius\FusBup\Parser\Rule;
use ju1ius\FusBup\SuffixTree\Node;

final class SuffixTreeCompiler
{
    /**
     * @param Rule[] $rules
     */
    public function compile(array $rules): string
    {
        $tree = SuffixTreeBuilder::build($rules);
        $code = CodeBuilder::forFile()->raw('return ');
        $this->compileNode($tree->root, $code);
        $code->raw(";\n");

        return (string)$code;
    }

    private function compileNode(Node|int $node, CodeBuilder $code): void
    {
        if (\is_int($node)) {
            $code->int($node);
            return;
        }

        $code
            ->new(Node::class)->raw('(')
            ->int($node->flags)
            ->raw(", [\n")
            ->indent()
            ->each($node->children, function(Node|int $v, $k, CodeBuilder $code) {
                $code->write('')->repr((string)$k)->raw(' => ');
                $this->compileNode($v, $code);
                $code->raw(",\n");
            })
            ->dedent()
            ->write('])')
        ;
    }
}
