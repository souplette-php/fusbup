<?php declare(strict_types=1);

namespace Souplette\FusBup\Lookup;

use Souplette\FusBup\Exception\PrivateEffectiveTLDException;
use Souplette\FusBup\Exception\UnknownTLDException;
use Souplette\FusBup\Lookup\Dafsa\IncrementalLookup;
use Souplette\FusBup\Lookup\Dafsa\Result;
use Souplette\FusBup\Utils\Idn;

/**
 * @internal
 */
final class Dafsa implements LookupInterface
{
    public const HEADER = ".DAFSA@FUSBUP_0\n";

    public function __construct(
        private readonly string $buffer,
    ) {
    }

    public function isEffectiveTLD(string $domain, int $flags = self::FORBID_NONE): bool
    {
        $domain = Idn::toAscii($domain);
        try {
            [$result, $suffixLength] = $this->reverseLookup($domain, $flags);
        } catch (PrivateEffectiveTLDException) {
            return false;
        }
        if ($result === Result::NotFound) {
            if ($flags & self::FORBID_UNKNOWN) {
                return false;
            }
            return !str_contains($domain, '.');
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

    public function getEffectiveTLD(string $domain, int $flags = self::FORBID_NONE): string
    {
        $domain = Idn::toAscii($domain);
        [$result, $suffixLength] = $this->reverseLookup($domain, $flags);
        // No rule found in the registry.
        if ($result === Result::NotFound) {
            // If we allow unknown registries, return the length of last subcomponent.
            if ($flags & self::FORBID_UNKNOWN) {
                throw new UnknownTLDException($domain);
            }
            if (false !== $lastDot = strrpos($domain, '.')) {
                return substr($domain, $lastDot + 1);
            }
            return $domain;
        }
        // Exception rules override wildcard rules when the domain is an exact match,
        // but wildcards take precedence when there's a subdomain.
        if ($result & Result::Wildcard) {
            // If the complete host matches, then the host is the wildcard suffix
            if ($suffixLength === \strlen($domain)) {
                return $domain;
            }
            assert($suffixLength + 2 <= \strlen($domain));
            assert($domain[-$suffixLength - 1] === '.');
            if (false === $precedingDot = strrpos($domain, '.', -$suffixLength - 2)) {
                // If no preceding dot, then the host is the registry itself.
                return $domain;
            }
            // Return suffix size plus size of subdomain.
            return substr($domain, $precedingDot + 1);
        }
        if ($result & Result::Exception) {
            if (false !== $firstDot = strpos($domain, '.', -$suffixLength)) {
                return substr($domain, $firstDot + 1);
            }
            // see the split() method for context
            throw new \LogicException('Exception rule for top-level domain'); // @codeCoverageIgnore
        }

        return substr($domain, -$suffixLength);
    }

    public function split(string $domain, int $flags = self::FORBID_NONE): array
    {
        $domain = Idn::toAscii($domain);
        [$result, $suffixLength] = $this->reverseLookup($domain, $flags);
        // No rule found in the registry.
        if ($result === Result::NotFound) {
            // If we allow unknown registries, return the last subcomponent is the eTLD.
            if ($flags & self::FORBID_UNKNOWN) {
                throw new UnknownTLDException($domain);
            }
            $parts = explode('.', $domain);
            $tail = array_pop($parts);
            return [$parts, [$tail]];
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
                // If no preceding dot, then the host is the registry itself.
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
            if (false !== $firstDot = strpos($domain, '.', -$suffixLength)) {
                $head = substr($domain, 0, $firstDot);
                $tail = substr($domain, $firstDot + 1);
                return [
                    explode('.', $head),
                    explode('.', $tail),
                ];
            }
            // If we get here, we had an exception rule with no dots (e.g. "!foo").
            // This would only be valid if we had a corresponding wildcard rule,
            // which would have to be "*".
            // But the parser explicitly disallows that case, so this kind of rule is invalid.
            throw new \LogicException('Exception rule for top-level domain'); // @codeCoverageIgnore
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
     * This method assumes the graph has been compiled in reverse mode.
     */
    private function reverseLookup(string $key, int $flags): array
    {
        $lookup = new IncrementalLookup($this->buffer);
        $result = Result::NotFound;
        $suffixLength = 0;
        // Look up host from right to left.
        for ($i = \strlen($key) - 1; $i >= 0 && $lookup->advance($key[$i]); $i--) {
            // Only host itself or a part that follows a dot can match.
            if ($i === 0 || $key[$i - 1] === '.') {
                $value = $lookup->getResultForCurrentSequence();
                if ($value === Result::NotFound) {
                    continue;
                }
                if (($value & Result::Private) && ($flags & self::FORBID_PRIVATE)) {
                    throw new PrivateEffectiveTLDException($key);
                }
                // Save length and return value.
                // Since hosts are looked up from right to left, the last saved value will be from the longest match.
                $result = $value;
                $suffixLength = \strlen($key) - $i;
            }
        }
        return [$result, $suffixLength];
    }
}
