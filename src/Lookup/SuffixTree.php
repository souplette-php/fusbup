<?php declare(strict_types=1);

namespace ju1ius\FusBup\Lookup;

use ju1ius\FusBup\Exception\DomainLookupException;
use ju1ius\FusBup\Exception\PrivateDomainException;
use ju1ius\FusBup\Exception\UnknownDomainException;
use ju1ius\FusBup\Lookup\SuffixTree\Flags;
use ju1ius\FusBup\Lookup\SuffixTree\Node;
use ju1ius\FusBup\Utils\Idn;

/**
 * @link https://github.com/publicsuffix/list/wiki/Format#algorithm
 * @internal
 */
final class SuffixTree implements PslLookupInterface
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
        } catch (DomainLookupException) {
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
            $nodeFlags = \is_int($node) ? $node : $node->flags;
            if ($nodeFlags === Flags::CONTINUE) {
                continue;
            }
            if (($nodeFlags & Flags::PRIVATE) && !($flags & self::ALLOW_PRIVATE)) {
                throw new PrivateDomainException();
            }
            if ($nodeFlags & Flags::STORE) {
                $matches[] = $path;
            }
            if ($nodeFlags & Flags::WILDCARD) {
                $matches[] = $path;
                if ($next = $labels[$i - 1] ?? null) {
                    $matches[] = [$next, ...$path];
                }
            }
            if ($nodeFlags & Flags::EXCLUDE) {
                $matches = array_filter($matches, fn($p) => $p !== $path);
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
