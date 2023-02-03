<?php declare(strict_types=1);

namespace ju1ius\FusBup;

use ju1ius\FusBup\Exception\ForbiddenDomainException;
use ju1ius\FusBup\Exception\PrivateETLDException;
use ju1ius\FusBup\Exception\UnknownTLDException;
use ju1ius\FusBup\Loader\LoaderInterface;
use ju1ius\FusBup\Loader\PhpFileLoader;
use ju1ius\FusBup\Lookup\LookupInterface;
use ju1ius\FusBup\Utils\Idn;

final class PublicSuffixList
{
    public const FORBID_NONE = LookupInterface::FORBID_NONE;
    /**
     * Forbids private suffixes (not in the ICANN section of the public suffix list).
     */
    public const FORBID_PRIVATE = LookupInterface::FORBID_PRIVATE;
    /**
     * Allows unknown TLDs.
     */
    public const FORBID_UNKNOWN = LookupInterface::FORBID_UNKNOWN;

    private readonly LookupInterface $lookup;

    public function __construct(
        private readonly LoaderInterface $loader = new PhpFileLoader(__DIR__ . '/Resources/psl.php'),
    ) {
    }

    /**
     * Returns whether the given domain is an effective TLD.
     */
    public function isEffectiveTLD(string $domain, int $flags = self::FORBID_NONE): bool
    {
        return $this->getLookup()->isEffectiveTLD($domain, $flags);
    }

    /**
     * Returns the effective TLD of a domain.
     *
     * @throws UnknownTLDException If FORBID_UNKNOWN flag is set and the TLD is not in the public suffix list.
     * @throws PrivateETLDException If FORBID_PRIVATE flag is set and the effective TLD is not in the ICANN section
     *                                of the public suffix list
     */
    public function getEffectiveTLD(string $domain, int $flags = self::FORBID_NONE): string
    {
        return Idn::toUnicode($this->getLookup()->getEffectiveTLD($domain, $flags));
    }

    /**
     * Splits a domain into it's private and effective TLD parts.
     *
     * @returns array{string, string}
     *
     * @throws UnknownTLDException If FORBID_UNKNOWN flag is set and the TLD is not in the public suffix list.
     * @throws PrivateETLDException If FORBID_PRIVATE flag is set and the effective TLD is not in the ICANN section
     *                                of the public suffix list
     */
    public function splitEffectiveTLD(string $domain, int $flags = self::FORBID_NONE): ?array
    {
        [$head, $tail] = $this->getLookup()->split($domain, $flags);
        return [
            $head ? Idn::toUnicode($head) : '',
            Idn::toUnicode($tail),
        ];
    }

    /**
     * Returns the registrable part (AKA eTLD+1) of a domain.
     *
      *@throws UnknownTLDException If FORBID_UNKNOWN flag is set and the TLD is not in the public suffix list.
     * @throws PrivateETLDException If FORBID_PRIVATE flag is set and the effective TLD is not in the ICANN section
     *                                of the public suffix list
     */
    public function getRegistrableDomain(string $domain, int $flags = self::FORBID_NONE): ?string
    {
        [$head, $tail] = $this->getLookup()->split($domain, $flags);
        if (!$head) {
            return null;
        }
        array_unshift($tail, array_pop($head));
        return Idn::toUnicode($tail);
    }

    /**
     * Splits a domain into it's private and registrable parts.
     *
     * @throws UnknownTLDException If FORBID_UNKNOWN flag is set and the TLD is not in the public suffix list.
     * @throws PrivateETLDException If FORBID_PRIVATE flag is set and the effective TLD is not in the ICANN section
     *                                of the public suffix list
     */
    public function splitRegistrableDomain(string $domain, int $flags = self::FORBID_NONE): ?array
    {
        [$head, $tail] = $this->getLookup()->split($domain, $flags);
        if (!$head) {
            return null;
        }
        array_unshift($tail, array_pop($head));
        return [
            $head ? Idn::toUnicode($head) : '',
            Idn::toUnicode($tail),
        ];
    }

    public function isCookieDomainAcceptable(string $requestDomain, string $cookieDomain, int $flags = self::FORBID_NONE): bool
    {
        // A string domain-matches a given domain string if at least one of the following conditions hold:
        // 1. The domain string and the string are identical.
        //    Note that both the domain string and the string will have been canonicalized to lower case at this point.
        $requestDomain = strtolower($requestDomain);
        // cookie domain is restricted to ASCII characters, so we don't need to run Idn::toAscii()
        $cookieDomain = strtolower(ltrim($cookieDomain, '.'));
        if ($requestDomain === $cookieDomain) {
            return true;
        }
        // 2. All the following conditions hold:
        if (
            // The domain string is a suffix of the string
            str_ends_with($requestDomain, $cookieDomain)
            // The last character of the string that is not included in the domain string is "."
            && $requestDomain[-\strlen($cookieDomain) - 1] === '.'
            // The string is a host name (i.e., not an IP address).
            && filter_var($requestDomain, \FILTER_VALIDATE_IP) === false
        ) {
            // cookie domain matches, but it must be longer than the longest public suffix in $requestDomain
            try {
                $requestSuffix = $this->getEffectiveTLD($requestDomain, $flags);
                return \strlen($cookieDomain) > \strlen($requestSuffix);
            } catch (ForbiddenDomainException) {
                return false;
            }
        }

        return false;
    }

    private function getLookup(): LookupInterface
    {
        return $this->lookup ??= $this->loader->load();
    }
}
