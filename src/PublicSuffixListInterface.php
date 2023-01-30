<?php declare(strict_types=1);

namespace ju1ius\FusBup;

interface PublicSuffixListInterface extends PslLookupInterface
{
    /**
     * @link https://httpwg.org/specs/rfc6265.html#cookie-domain
     */
    public function isCookieDomainAcceptable(string $requestDomain, string $cookieDomain): bool;
}
