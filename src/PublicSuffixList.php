<?php declare(strict_types=1);

namespace ju1ius\FusBup;

use ju1ius\FusBup\Loader\PhpFileLoader;
use ju1ius\FusBup\Loader\LoaderInterface;
use ju1ius\FusBup\Utils\Idn;

final class PublicSuffixList implements PublicSuffixListInterface
{
    private readonly PslLookupInterface $lookup;

    public function __construct(
        private readonly LoaderInterface $loader = new PhpFileLoader(__DIR__ . '/Resources/psl.php'),
    ) {
    }

    public function isPublicSuffix(string $domain, int $flags = self::ALLOW_ALL): bool
    {
        return $this->getLookup()->isPublicSuffix($domain, $flags);
    }

    public function getPublicSuffix(string $domain, int $flags = self::ALLOW_ALL): string
    {
        return Idn::toUnicode($this->getLookup()->getPublicSuffix($domain));
    }

    public function splitPublicSuffix(string $domain): array
    {
        return $this->getLookup()->splitPublicSuffix($domain);
    }

    public function getRegistrableDomain(string $domain): ?string
    {
        return $this->getLookup()->getRegistrableDomain($domain);
    }

    public function splitRegistrableDomain(string $domain): ?array
    {
        return $this->getLookup()->splitRegistrableDomain($domain);
    }

    public function isCookieDomainAcceptable(string $requestDomain, string $cookieDomain): bool
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
            $requestSuffix = $this->getPublicSuffix($requestDomain);
            return \strlen($cookieDomain) > \strlen($requestSuffix);
        }

        return false;
    }

    private function getLookup(): PslLookupInterface
    {
        return $this->lookup ??= $this->loader->load();
    }
}
