<?php declare(strict_types=1);

namespace Souplette\FusBup\Lookup;

use Souplette\FusBup\Exception\ForbiddenDomainException;
use Souplette\FusBup\Exception\PrivateEffectiveTLDException;
use Souplette\FusBup\Exception\UnknownTLDException;
use Souplette\FusBup\Lookup\SuffixTree\Flags;
use Souplette\FusBup\Lookup\SuffixTree\Node;
use Souplette\FusBup\Utils\Idn;

/**
 * @link https://github.com/publicsuffix/list/wiki/Format#algorithm
 * @internal
 */
final class SuffixTree implements LookupInterface
{
    public function __construct(
        public readonly Node $root,
    ) {
    }

    public function isEffectiveTLD(string $domain, int $flags = self::FORBID_NONE): bool
    {
        try {
            [$head, $tail] = $this->split($domain, $flags);
            return !$head && $tail;
        } catch (ForbiddenDomainException) {
            return false;
        }
    }

    public function getEffectiveTLD(string $domain, int $flags = self::FORBID_NONE): string
    {
        [, $tail] = $this->split($domain, $flags);
        return implode('.', $tail);
    }

    public function split(string $domain, int $flags = self::FORBID_NONE): array
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
            if (($nodeFlags & Flags::PRIVATE) && ($flags & self::FORBID_PRIVATE)) {
                throw new PrivateEffectiveTLDException($domain);
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
            if ($flags & self::FORBID_UNKNOWN) {
                throw new UnknownTLDException($domain);
            }
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
