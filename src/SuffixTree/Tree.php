<?php declare(strict_types=1);

namespace ju1ius\FusBup\SuffixTree;

use ju1ius\FusBup\Exception\UnknownOpcodeException;
use ju1ius\FusBup\Utils\Idn;

/**
 * @link https://github.com/publicsuffix/list/wiki/Format#algorithm
 * @internal
 */
final class Tree
{
    public function __construct(
        public readonly Node $root,
    ) {
    }

    /**
     * Returns a tuple containing the private labels and the public labels.
     * Labels are returned in their ASCII canonical form.
     *
     * $tree->split('a.b.co.uk') => [['a', 'b'], ['co', 'uk']]
     *
     * @return array<string[], string[]>
     */
    public function split(string $domain): array
    {
        $labels = explode('.', Idn::toAscii($domain));
        $node = $this->root;
        $matches = [];
        $path = [];
        for ($i = \array_key_last($labels); $i >= 0; $i--) {
            $label = $labels[$i];
            if (null === $node = $node->children[$label] ?? null) {
                break;
            }
            array_unshift($path, $label);
            $opcode = match (true) {
                \is_int($node) => $node,
                default => $node->op,
            };
            switch ($opcode) {
                case Opcodes::CONTINUE:
                    break;
                case Opcodes::STORE:
                    $matches[] = $path;
                    break;
                case Opcodes::WILDCARD:
                    $matches[] = $path;
                    if ($next = $labels[$i - 1] ?? null) {
                        $matches[] = [$next, ...$path];
                    }
                    break;
                case Opcodes::EXCLUDE:
                    $matches = array_filter($matches, fn($p) => $p !== $path);
                    break;
                default:
                    throw new UnknownOpcodeException($opcode);
            }
        }
        // No matches: the public suffix is the rightmost label.
        if (!$matches) {
            return [
                array_slice($labels, 0, -1),
                array_slice($labels, -1, 1),
            ];
        }

        $tail = end($matches);
        $head = array_slice($labels, 0, -\count($tail));

        return [$head, $tail];
    }
}
