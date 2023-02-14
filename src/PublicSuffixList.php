<?php declare(strict_types=1);

namespace Souplette\FusBup;

use Souplette\FusBup\Loader\DafsaLoader;
use Souplette\FusBup\Loader\LoaderInterface;
use Souplette\FusBup\Lookup\LookupInterface;

final class PublicSuffixList implements PublicSuffixListInterface
{
    private readonly LookupInterface $lookup;

    public function __construct(
        private readonly LoaderInterface $loader = new DafsaLoader(),
    ) {
    }

    public function isEffectiveTLD(string $domain, int $flags = self::FORBID_NONE): bool
    {
        return $this->getLookup()->isEffectiveTLD($domain, $flags);
    }

    public function getEffectiveTLD(string $domain, int $flags = self::FORBID_NONE): string
    {
        return $this->getLookup()->getEffectiveTLD($domain, $flags);
    }

    public function splitEffectiveTLD(string $domain, int $flags = self::FORBID_NONE): ?array
    {
        [$head, $tail] = $this->getLookup()->split($domain, $flags);
        return [
            implode('.', $head),
            implode('.', $tail),
        ];
    }

    public function getRegistrableDomain(string $domain, int $flags = self::FORBID_NONE): ?string
    {
        [$head, $tail] = $this->getLookup()->split($domain, $flags);
        if (!$head) {
            return null;
        }
        array_unshift($tail, array_pop($head));
        return implode('.', $tail);
    }

    public function splitRegistrableDomain(string $domain, int $flags = self::FORBID_NONE): ?array
    {
        [$head, $tail] = $this->getLookup()->split($domain, $flags);
        if (!$head) {
            return null;
        }
        array_unshift($tail, array_pop($head));
        return [
            implode('.', $head),
            implode('.', $tail),
        ];
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
            $requestSuffix = $this->getEffectiveTLD($requestDomain);
            return \strlen($cookieDomain) > \strlen($requestSuffix);
        }

        return false;
    }

    private function getLookup(): LookupInterface
    {
        return $this->lookup ??= $this->loader->load();
    }
}
