# ju1ius/fusbup

[![codecov](https://codecov.io/gh/ju1ius/fusbup/branch/main/graph/badge.svg?token=bcrU1ru7IF)](https://codecov.io/gh/ju1ius/fusbup)

PHP library to query the [Mozilla public suffix list](https://publicsuffix.org/).

## Installation

```sh
composer require ju1ius/fusbup
```

## Basic usage

### Querying effective top-level domains (eTLD)

```php
use ju1ius\FusBup\PublicSuffixList;

$psl = new PublicSuffixList();
// get the eTLD (short for Effective Top-Level Domain) of a domain
assert($psl->getEffectiveTLD('foo.co.uk') === 'co.uk');
// check if a domain is an eTLD
assert($psl->isEffectiveTLD('fukushima.jp'));
// split a domain into it's private and eTLD parts
assert($psl->splitEffectiveTLD('www.foo.co.uk') === ['www.foo', 'co.uk']);
```

### Querying registrable domains (AKA eTLD+1)

```php
use ju1ius\FusBup\PublicSuffixList;

$psl = new PublicSuffixList();
// get the registrable part (eTLD+1) of a domain
assert($psl->getRegistrableDomain('www.foo.co.uk') === 'foo.co.uk');
// split a domain into it's private and registrable parts.
assert($psl->splitRegistrableDomain('www.foo.co.uk') === ['www', 'foo.co.uk']);
```

### Checking the applicability of a cookie domain

The `PublicSuffixList` class implements the
[RFC6265 algorithm](https://httpwg.org/specs/rfc6265.html#cookie-domain)
for matching a cookie domain against a request domain.

```php
use ju1ius\FusBup\PublicSuffixList;

$psl = new PublicSuffixList();
// check if a cookie domain is applicable to a hostname
$requestDomain = 'my.domain.com'
$cookieDomain = '.domain.com';
assert($psl->isCookieDomainAcceptable($requestDomain, $cookieDomain));
// cookie are rejected if their domain is an eTLD:
assert(false === $psl->isCookieDomainAcceptable('foo.com', '.com'))
```
