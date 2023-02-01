<?php declare(strict_types=1);

namespace ju1ius\FusBup\Dafsa;

use ju1ius\FusBup\Exception\UnknownDomainException;
use ju1ius\FusBup\PslLookupInterface;
use ju1ius\FusBup\Utils\Idn;

/**
 * @internal
 * @todo cleanup this implementation.
 * @todo move IDN normalization to the PublicSuffixList class?
 */
final class Graph implements PslLookupInterface
{
    public const HEADER = ".DAFSA@PSL_0   \n";

    public function __construct(
        private readonly string $buffer,
    ) {
    }

    public function isPublicSuffix(string $domain, int $flags = self::ALLOW_ALL): bool
    {
        $domain = Idn::toAscii($domain);
        [$result, $suffixLength] = IncrementalLookup::reverseLookup($this->buffer, $domain);
        if ($result === Result::NotFound) {
            if ($flags & self::ALLOW_UNKNOWN) {
                return !str_contains($domain, '.');
            }
            return false;
        }
        if ($result & Result::Exception) {
            return false;
        }
        if ($result & Result::Wildcard) {
            if ($suffixLength === \strlen($domain)) {
                // wildcard *.foo.bar implicitly make foo.bar a public suffix
                // definitely a match, no matter if the found rule is a wildcard or not
                return true;
            }
            return false === strrpos($domain, '.', -$suffixLength - 2);
        }

        return $suffixLength === \strlen($domain);
    }

    public function getPublicSuffix(string $domain, int $flags = self::ALLOW_ALL): string
    {
        $domain = Idn::toAscii($domain);
        [$result, $suffixLength] = IncrementalLookup::reverseLookup($this->buffer, $domain);
        // No rule found in the registry.
        if ($result === Result::NotFound) {
            // If we allow unknown registries, return the length of last subcomponent.
            if ($flags & self::ALLOW_UNKNOWN) {
                if (false !== $lastDot = strrpos($domain, '.')) {
                    return substr($domain, $lastDot + 1);
                }
                return $domain;
            }
            throw new UnknownDomainException();
        }
        // Exception rules override wildcard rules when the domain is an exact match,
        // but wildcards take precedence when there's a subdomain.
        if ($result & Result::Wildcard) {
            // If the complete host matches, then the host is the wildcard suffix,
            // so return 0.
            if ($suffixLength === \strlen($domain)) {
                // todo: or nothing?
                return $domain;
            }
            assert($suffixLength + 2 <= \strlen($domain));
            assert($domain[-$suffixLength - 1] === '.');
            if (false === $precedingDot = strrpos($domain, '.', -$suffixLength - 2)) {
                // If no preceding dot, then the host is the registry itself, so return 0.
                // FIXME
                return $domain;
            }
            // Return suffix size plus size of subdomain.
            return substr($domain, $precedingDot + 1);
        }
        if ($result & Result::Exception) {
            if (false === $firstDot = strpos($domain, '.', -$suffixLength)) {
                // If we get here, we had an exception rule with no dots (e.g. "!foo").
                // This would only be valid if we had a corresponding wildcard rule,
                // which would have to be "*".
                // But we explicitly disallow that case, so this kind of rule is invalid.
                // TODO(https://crbug.com/459802): This assumes that all wildcard entries,
                // such as *.foo.invalid, also have their parent, foo.invalid, as an entry
                // on the PSL, which is why it returns the length of foo.invalid.
                // This isn't entirely correct.
                return '';
            }
            return substr($domain, $firstDot + 1);
        }

        return substr($domain, -$suffixLength);
    }

    public function splitPublicSuffix(string $domain): array
    {
        [$head, $tail] = $this->split($domain);
        return [
            $head ? Idn::toUnicode($head) : '',
            Idn::toUnicode($tail),
        ];
    }

    public function getRegistrableDomain(string $domain, int $flags = self::ALLOW_ALL): ?string
    {
        [$head, $tail] = $this->split($domain);
        if (!$head) {
            return null;
        }
        array_unshift($tail, array_pop($head));
        return Idn::toUnicode($tail);
    }

    public function splitRegistrableDomain(string $domain): ?array
    {
        [$head, $tail] = $this->split($domain);
        if (!$head) {
            return null;
        }
        array_unshift($tail, array_pop($head));
        return [
            $head ? Idn::toUnicode($head) : '',
            Idn::toUnicode($tail),
        ];
    }

    public function split(string $domain, int $flags = self::ALLOW_ALL): array
    {
        $domain = Idn::toAscii($domain);
        [$result, $suffixLength] = IncrementalLookup::reverseLookup($this->buffer, $domain);
        $head = $tail = [];
        // No rule found in the registry.
        if ($result === Result::NotFound) {
            // If we allow unknown registries, return the last subcomponent is the eTLD.
            if ($flags & self::ALLOW_UNKNOWN) {
                $parts = explode('.', $domain);
                $tail = array_pop($parts);
                return [$parts, [$tail]];
            }
            throw new UnknownDomainException();
        }
        // Exception rules override wildcard rules when the domain is an exact match,
        // but wildcards take precedence when there's a subdomain.
        if ($result & Result::Wildcard) {
            // If the complete host matches, then the host is the wildcard suffix.
            if ($suffixLength === \strlen($domain)) {
                return [[], explode('.', $domain)];
            }
            assert($suffixLength + 2 <= \strlen($domain));
            assert($domain[-$suffixLength - 1] === '.');
            if (false === $precedingDot = strrpos($domain, '.', -$suffixLength - 2)) {
                // If no preceding dot, then the host is the registry itself, so return 0.
                // FIXME
                return [[], explode('.', $domain)];
            }
            // Return suffix size plus size of subdomain.
            $head = substr($domain, 0, $precedingDot);
            $tail = substr($domain, $precedingDot + 1);
            return [
                explode('.', $head),
                explode('.', $tail),
            ];
        }
        if ($result & Result::Exception) {
            if (false === $firstDot = strpos($domain, '.', -$suffixLength)) {
                // If we get here, we had an exception rule with no dots (e.g. "!foo").
                // This would only be valid if we had a corresponding wildcard rule,
                // which would have to be "*".
                // But we explicitly disallow that case, so this kind of rule is invalid.
                // TODO(https://crbug.com/459802): This assumes that all wildcard entries,
                // such as *.foo.invalid, also have their parent, foo.invalid, as an entry
                // on the PSL, which is why it returns the length of foo.invalid.
                // This isn't entirely correct.
                return [[], []];
            }
            $head = substr($domain, 0, $firstDot);
            $tail = substr($domain, $firstDot + 1);
            return [
                explode('.', $head),
                explode('.', $tail),
            ];
        }

        if ($suffixLength === \strlen($domain)) {
            return [[], explode('.', $domain)];
        }
        $head = substr($domain, 0, -$suffixLength - 1);
        $tail = substr($domain, -$suffixLength);
        return [
            explode('.', $head),
            explode('.', $tail),
        ];
    }

    /**
     * @todo remove this once we've figured out all the corner cases.
     * @codeCoverageIgnore
     */
    private function getRegistryLength(string $domain, bool $allowUnknown = true): int
    {
        [$result, $suffixLength] = IncrementalLookup::reverseLookup($this->buffer, $domain);
        assert($suffixLength <= \strlen($domain));
        // No rule found in the registry.
        if ($result === Result::NotFound) {
            // If we allow unknown registries, return the length of last subcomponent.
            if ($allowUnknown) {
                if (false !== $lastDot = strrpos($domain, '.')) {
                    return \strlen($domain) - $lastDot - 1;
                }
            }
            return 0;
        }
        // Exception rules override wildcard rules when the domain is an exact match,
        // but wildcards take precedence when there's a subdomain.
        if ($result & Result::Wildcard) {
            // If the complete host matches, then the host is the wildcard suffix,
            // so return 0.
            if ($suffixLength === \strlen($domain)) {
                return 0;
            }
            assert($suffixLength + 2 <= \strlen($domain));
            assert($domain[-$suffixLength - 1] === '.');
            if (false === $precedingDot = strrpos($domain, '.', -$suffixLength - 2)) {
                // If no preceding dot, then the host is the registry itself, so return 0.
                return 0;
            }
            // Return suffix size plus size of subdomain.
            return \strlen($domain) - $precedingDot - 1;
        }
        if ($result & Result::Exception) {
            if (false === $firstDot = strpos($domain, '.', -$suffixLength)) {
                // If we get here, we had an exception rule with no dots (e.g. "!foo").
                // This would only be valid if we had a corresponding wildcard rule,
                // which would have to be "*".
                // But we explicitly disallow that case, so this kind of rule is invalid.
                // TODO(https://crbug.com/459802): This assumes that all wildcard entries,
                // such as *.foo.invalid, also have their parent, foo.invalid, as an entry
                // on the PSL, which is why it returns the length of foo.invalid.
                // This isn't entirely correct.
                return 0;
            }
            return \strlen($domain) - $firstDot - 1;
        }
        // If a complete match, then the host is the registry itself, so return 0.
        if ($suffixLength === \strlen($domain)) {
            return 0;
        }

        return $suffixLength;
    }
}
