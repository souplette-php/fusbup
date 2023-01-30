<?php declare(strict_types=1);

namespace ju1ius\FusBup\SuffixTree;

use ju1ius\FusBup\Exception\UnknownDomainException;
use ju1ius\FusBup\Exception\UnknownOpcodeException;
use ju1ius\FusBup\PslLookupInterface;
use ju1ius\FusBup\Utils\Idn;

/**
 * @link https://github.com/publicsuffix/list/wiki/Format#algorithm
 * @internal
 */
final class Tree implements PslLookupInterface
{
    public function __construct(
        public readonly Node $root,
    ) {
    }

    public function isPublicSuffix(string $domain, int $flags = self::ALLOW_ALL): bool
    {
        try {
            [$head, $tail] = $this->split($domain, $flags);
            return !$head && $tail;
        } catch (UnknownDomainException) {
            return false;
        }
    }

    public function getPublicSuffix(string $domain, int $flags = self::ALLOW_ALL): string
    {
        [, $tail] = $this->split($domain, $flags);
        return Idn::toUnicode($tail);
    }
    public function splitPublicSuffix(string $domain): array
    {
        [$head, $tail] = $this->split($domain);
        return [
            $head ? Idn::toUnicode($head) : '',
            Idn::toUnicode($tail),
        ];
    }

    public function getRegistrableDomain(string $domain): ?string
    {
        [$head, $tail] = $this->split($domain);
        if (!$head) {
            return null;
        }
        array_unshift($tail, array_pop($head));
        return Idn::toUnicode($tail);
    }

    public function splitRegistrableDomain(string $domain): ?array
    {
        [$head, $tail] = $this->split($domain);
        if (!$head) {
            return null;
        }
        array_unshift($tail, array_pop($head));
        return [
            $head ? Idn::toUnicode($head) : '',
            Idn::toUnicode($tail),
        ];
    }

    public function split(string $domain, int $flags = self::ALLOW_ALL): array
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
            // TODO: private flag
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
            if ($flags & self::ALLOW_UNKNOWN) {
                return [
                    array_slice($labels, 0, -1),
                    array_slice($labels, -1, 1),
                ];
            }
            throw new UnknownDomainException();
        }

        $tail = end($matches);
        $head = array_slice($labels, 0, -\count($tail));

        return [$head, $tail];
    }
}
