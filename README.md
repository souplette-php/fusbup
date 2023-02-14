# souplette/fusbup

[![codecov](https://codecov.io/gh/souplette-php/fusbup/branch/main/graph/badge.svg?token=bcrU1ru7IF)](https://codecov.io/gh/souplette-php/fusbup)

A fast and memory-efficient PHP library to query the
[Mozilla public suffix list](https://publicsuffix.org/).

## Installation

```sh
composer require souplette/fusbup
```

## Basic usage

### Querying effective top-level domains (eTLD)

```php
use Souplette\FusBup\PublicSuffixList;

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
use Souplette\FusBup\PublicSuffixList;

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
use Souplette\FusBup\PublicSuffixList;

$psl = new PublicSuffixList();
// check if a cookie domain is applicable to a hostname
$requestDomain = 'my.domain.com'
$cookieDomain = '.domain.com';
assert($psl->isCookieDomainAcceptable($requestDomain, $cookieDomain));
// cookie are rejected if their domain is an eTLD:
assert(false === $psl->isCookieDomainAcceptable('foo.com', '.com'))
```

### Internationalized domain names

All `PublicSuffixList` methods that return domains
return them in their [normalized ASCII](https://url.spec.whatwg.org/#idna) form.

```php
use Souplette\FusBup\PublicSuffixList;
use Souplette\FusBup\Utils\Idn;

$psl = new PublicSuffixList();
assert($psl->getRegistrableDomain('☕.example') === 'xn--53h.example');
// use Idn::toUnicode() to convert them back to unicode if needed:
assert(Idn::toUnicode('xn--53h.example') === '☕.example');
```


## Performance

The public suffix list contains about 10 000 rules as of 2023.
In order to be maximally efficient for all uses cases,
the `PublicSuffixList` class can use two search algorithms
with different performance characteristics.

The first one (and the default) uses a [DAFSA](https://en.wikipedia.org/wiki/Deterministic_acyclic_finite_state_automaton)
compiled to a binary string (this is the algorithm used in the Gecko and Chromium engines).
The second one uses a compressed suffix tree compiled to PHP code.

Here is a summary of their respective pros and cons:

* DAFSA
  * 👍 more memory efficient (this is just a 50Kb string in memory)
  * 👍 faster to load (around 20μs on a SSD)
  * 👎 slower to search (in the order of 100 000 ops/sec)
* Suffix tree
  * 👎 less memory efficient (about 4Mb in memory)
  * 👎 slower to load (around 4ms without opcache, 500μs when using opcache preloading)
  * 👍 faster to search (in the order of 1 000 000 ops/sec)

Note that in both cases, the database will be lazily loaded.

### Which search algorithm should I use?

Well, it depends on your use case but based on the aforementioned characteristics
I would say: stick to the default (DAFSA) algorithm unless your app
is going to make more than a few hundreds searches per seconds.

### Tell me how can I use them?

Both algorithm can be used by passing the appropriate loader to the `PublicSuffixList` constructor.

#### DAFSA

```php
use Souplette\FusBup\Loader\DafsaLoader;
use Souplette\FusBup\PublicSuffixList;

$psl = new PublicSuffixList(new DafsaLoader());
// since DafsaLoader is the default, the following is equivalent:
$psl = new PublicSuffixList();
```

#### Suffix Tree

```php
use Souplette\FusBup\Loader\SuffixTreeLoader;
use Souplette\FusBup\PublicSuffixList;

$psl = new PublicSuffixList(new SuffixTreeLoader());
```

You should also configure opcache to preload the database:

In your `php.ini`:

```ini
opcache.enabled=1
opcache.preload=/path/to/my/preload-script.php
```

In your preload script:
```php
opcache_compile_file('/path/to/vendor/ju1ius/fusbup/Resources/psl.php');
```
