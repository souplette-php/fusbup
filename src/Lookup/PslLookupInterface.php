<?php declare(strict_types=1);

namespace ju1ius\FusBup\Lookup;

/**
 * @link https://github.com/publicsuffix/list/wiki/Format#algorithm
 * @internal
 */
interface PslLookupInterface
{
    const FORBID_NONE = 0;
    const FORBID_PRIVATE = 1;
    const FORBID_UNKNOWN = 2;

    public function isPublicSuffix(string $domain, int $flags = self::FORBID_NONE): bool;

    public function getPublicSuffix(string $domain, int $flags = self::FORBID_NONE): string;

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
    public function split(string $domain, int $flags = self::FORBID_NONE): array;
}
