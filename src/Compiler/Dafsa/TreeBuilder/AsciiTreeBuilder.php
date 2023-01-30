<?php declare(strict_types=1);

namespace ju1ius\FusBup\Compiler\Dafsa\TreeBuilder;

use ju1ius\FusBup\Compiler\Dafsa\Node;

final class AsciiTreeBuilder implements TreeBuilderInterface
{
    public function build(array $words): array
    {
        if (!$words) {
            throw new \RuntimeException('Empty word list.');
        }

        return array_map($this->processWord(...), $words);
    }

    private function processWord(string $word): Node
    {
        $chr = $word[0] ?? null;
        if ($chr <= "\x1F" || $chr >= "\x80") {
            throw new \RuntimeException('DAFSA words must be printable 7-bit ASCII');
        }
        if (\strlen($word) === 1) {
            return Node::of($chr & "\x0F", [Node::sink()]);
        }

        return Node::of($chr, [$this->processWord(substr($word, 1))]);
    }
}
