# ju1ius/fusbup

PHP library to query the [public suffix list](https://publicsuffix.org/).

## Installation

```sh
composer require ju1ius/fusbup
```

## Basic usage

### Querying public suffixes (AKA eTLD)

```php
use ju1ius\FusBup\PublicSuffixList;

$psl = new PublicSuffixList();
// get the public suffix (AKA eTLD) of a domain
assert($psl->getPublicSuffix('foo.co.uk') === 'co.uk');
// check if a domain is a public suffix
assert($psl->isPublicSuffix('fukushima.jp'));
// split a domain into it's private and public parts
assert($psl->splitPublicSuffix('www.foo.co.uk') === ['www.foo', 'co.uk']);
```

### Querying registrable domains (AKA eTLD+1)

```php
use ju1ius\FusBup\PublicSuffixList;

$psl = new PublicSuffixList();
// get the registrable part (AKA eTLD+1) of a domain
assert($psl->getRegistrableDomain('www.foo.co.uk') === 'foo.co.uk');
//
assert($psl->splitRegistrableDomain('www.foo.co.uk') === ['www', 'foo.co.uk']);
```

### Checking the applicability of a cookie domain

The `PublicSuffixList` class implements the
[RFC6265 algorithm](https://httpwg.org/specs/rfc6265.html#cookie-domain)
for matching a cookie domain against a request domain.

```php
use ju1ius\FusBup\PublicSuffixList;

$psl = new PublicSuffixList();
// check if a cookie domain is applicable to an hostname
$requestDomain = 'my.domain.com'
$cookieDomain = '.domain.com';
assert($psl->isCookieDomainAcceptable($requestDomain, $cookieDomain));
// cookie domains are rejected for public suffixes:
assert(false === $psl->isCookieDomainAcceptable('foo.com', '.com'))
```
