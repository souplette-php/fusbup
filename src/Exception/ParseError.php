<?php declare(strict_types=1);

namespace ju1ius\FusBup\Exception;

/**
 * An error happened while parsing the public suffix list.
 */
final class ParseError extends \RuntimeException implements FusBupException
{
    public static function from(\Throwable $err, ?string $message = null): self
    {
        return new self($message ?? $err->getMessage(), 0, $err);
    }

    public static function invalidRule(string $rule): self
    {
        return new self(sprintf('Invalid rule: "%s"', $rule));
    }
}
