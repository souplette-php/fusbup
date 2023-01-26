<?php declare(strict_types=1);

namespace ju1ius\FusBup;

use ju1ius\FusBup\SuffixTree\Tree;
use ju1ius\FusBup\Utils\Idn;

final class PublicSuffixList
{
    private readonly Tree $tree;

    /**
     * Returns whether the given domain is a public suffix.
     */
    public function isPublicSuffix(string $domain): bool
    {
        [$head, $tail] = $this->getTree()->split($domain);
        return !$head && $tail;
    }

    /**
     * Returns the public suffix of a domain.
     */
    public function getPublicSuffix(string $domain): string
    {
        [, $tail] = $this->getTree()->split($domain);
        return Idn::toUnicode($tail);
    }

    /**
     * Splits a domain into it's private and public suffix parts.
     *
     * @returns array{string, string}
     */
    public function splitPublicSuffix(string $domain): array
    {
        [$head, $tail] = $this->getTree()->split($domain);
        return [
            $head ? Idn::toUnicode($head) : '',
            Idn::toUnicode($tail),
        ];
    }

    /**
     * Returns the registrable part (AKA eTLD+1) of a domain.
     */
    public function getRegistrableDomain(string $domain): ?string
    {
        [$head, $tail] = $this->getTree()->split($domain);
        if (!$head) {
            return null;
        }
        $parts = [end($head), ...$tail];
        return Idn::toUnicode($parts);
    }

    private function getTree(): Tree
    {
        return $this->tree ??= new Tree(require __DIR__ . '/Resources/psl.php');
    }
}
