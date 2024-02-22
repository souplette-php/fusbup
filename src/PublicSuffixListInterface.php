<?php declare(strict_types=1);

namespace Souplette\FusBup;

use Souplette\FusBup\Exception\PrivateEffectiveTLDException;
use Souplette\FusBup\Exception\UnknownTLDException;
use Souplette\FusBup\Lookup\LookupInterface;

interface PublicSuffixListInterface
{
    public const FORBID_NONE = LookupInterface::FORBID_NONE;

    /**
     * Forbids private suffixes (not in the ICANN section of the public suffix list).
     */
    public const FORBID_PRIVATE = LookupInterface::FORBID_PRIVATE;

    /**
     * Forbids unknown TLDs.
     */
    public const FORBID_UNKNOWN = LookupInterface::FORBID_UNKNOWN;

    /**
     * Returns whether the given domain is an effective TLD.
     */
    public function isEffectiveTLD(string $domain, int $flags = self::FORBID_NONE): bool;

    /**
     * Returns the effective TLD of a domain.
     *
     * @throws UnknownTLDException If FORBID_UNKNOWN flag is set and the TLD is not in the public suffix list.
     * @throws PrivateEffectiveTLDException If FORBID_PRIVATE flag is set and the effective TLD
     * is not in the ICANN section of the public suffix list
     */
    public function getEffectiveTLD(string $domain, int $flags = self::FORBID_NONE): string;

    /**
     * Splits a domain into it's private and effective TLD parts.
     *
     * @returns array{string, string}
     *
     * @throws UnknownTLDException If FORBID_UNKNOWN flag is set and the TLD is not in the public suffix list.
     * @throws PrivateEffectiveTLDException If FORBID_PRIVATE flag is set and the effective TLD
     * is not in the ICANN section of the public suffix list
     */
    public function splitEffectiveTLD(string $domain, int $flags = self::FORBID_NONE): ?array;

    /**
     * Returns the registrable part (AKA eTLD+1) of a domain.
     *
     * @throws UnknownTLDException If FORBID_UNKNOWN flag is set and the TLD is not in the public suffix list.
     * @throws PrivateEffectiveTLDException If FORBID_PRIVATE flag is set and the effective TLD
     * is not in the ICANN section of the public suffix list
     */
    public function getRegistrableDomain(string $domain, int $flags = self::FORBID_NONE): ?string;

    /**
     * Splits a domain into it's private and registrable parts.
     *
     * @throws UnknownTLDException If FORBID_UNKNOWN flag is set and the TLD is not in the public suffix list.
     * @throws PrivateEffectiveTLDException If FORBID_PRIVATE flag is set and the effective TLD
     * is not in the ICANN section of the public suffix list
     */
    public function splitRegistrableDomain(string $domain, int $flags = self::FORBID_NONE): ?array;

    /**
     * Implements the {@link https://httpwg.org/specs/rfc6265.html#cookie-domain RFC6265 algorithm}
     * for matching a cookie domain against a request domain.
     */
    public function isCookieDomainAcceptable(string $requestDomain, string $cookieDomain): bool;
}
