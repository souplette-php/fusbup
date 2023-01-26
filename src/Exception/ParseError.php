<?php declare(strict_types=1);

namespace ju1ius\FusBup\Exception;

final class ParseError extends \RuntimeException implements FusBupException
{
    public static function invalidRule(string $rule): self
    {
        return new self(sprintf('Invalid rule: "%s"', $rule));
    }

    public static function idnError(string $domain): self
    {
        return new self(sprintf(
            'Could not canonicalize domain name: "%s"',
            $domain,
        ));
    }
}
