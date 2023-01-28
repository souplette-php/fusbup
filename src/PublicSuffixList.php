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

    /**
     * @link https://httpwg.org/specs/rfc6265.html#cookie-domain
     */
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
            && $requestDomain[\strlen($requestDomain) - \strlen($cookieDomain) - 1] === '.'
            // The string is a host name (i.e., not an IP address).
            && filter_var($requestDomain, \FILTER_VALIDATE_IP) === false
        ) {
            // cookie domain matches, but it must be longer than the longest public suffix in $requestDomain
            $requestSuffix = $this->getPublicSuffix($requestDomain);
            return \strlen($cookieDomain) > \strlen($requestSuffix);
        }

        return false;
    }

    private function getTree(): Tree
    {
        return $this->tree ??= new Tree(require __DIR__ . '/Resources/psl.php');
    }
}
