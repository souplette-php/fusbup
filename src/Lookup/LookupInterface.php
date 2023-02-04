<?php declare(strict_types=1);

namespace ju1ius\FusBup\Lookup;

/**
 * @link https://github.com/publicsuffix/list/wiki/Format#algorithm
 * @internal
 */
interface LookupInterface
{
    public const FORBID_NONE = 0;
    public const FORBID_PRIVATE = 1;
    public const FORBID_UNKNOWN = 2;

    public function isEffectiveTLD(string $domain, int $flags = self::FORBID_NONE): bool;

    public function getEffectiveTLD(string $domain, int $flags = self::FORBID_NONE): string;

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
