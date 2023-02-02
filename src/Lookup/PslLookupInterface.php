<?php declare(strict_types=1);

namespace ju1ius\FusBup\Lookup;

/**
 * @link https://github.com/publicsuffix/list/wiki/Format#algorithm
 * @internal
 */
interface PslLookupInterface
{
    const ALLOW_NONE = 0;
    const ALLOW_PRIVATE = 1;
    const ALLOW_UNKNOWN = 2;
    const ALLOW_ALL = self::ALLOW_PRIVATE | self::ALLOW_UNKNOWN;

    /**
     * Returns whether the given domain is a public suffix.
     */
    public function isPublicSuffix(string $domain, int $flags = self::ALLOW_ALL): bool;

    /**
     * Returns the public suffix of a domain.
     */
    public function getPublicSuffix(string $domain, int $flags = self::ALLOW_ALL): string;

    /**
     * Splits a domain into it's private and public suffix parts.
     *
     * @returns array{string, string}
     */
    public function splitPublicSuffix(string $domain): array;

    /**
     * Returns the registrable part (AKA eTLD+1) of a domain.
     */
    public function getRegistrableDomain(string $domain): ?string;

    /**
     * Splits a domain into it's private and registrable parts.
     */
    public function splitRegistrableDomain(string $domain): ?array;

    /**
     * Returns a tuple containing the private labels and the public labels.
     * Labels are returned in their ASCII canonical form.
     *
     * <code>
     * $lookup->split('a.b.co.uk') => [['a', 'b'], ['co', 'uk']]
     * </code>
     *
     * @return array<string[], string[]>
     */
    //public function split(string $domain): array;
}
