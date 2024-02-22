<?php declare(strict_types=1);

namespace Souplette\FusBup\Compiler\Exception;

use Souplette\FusBup\Compiler\Parser\Rule;
use Souplette\FusBup\Exception\FusBupException;

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

    public static function duplicateRule(Rule $rule): self
    {
        return new self(sprintf(
            'Duplicate rule: "%s"',
            $rule,
        ));
    }

    public static function exceptionRuleWithoutMatchingWildcard(Rule $rule): self
    {
        return new self(sprintf(
            'Exception rule without matching wildcard: "%s"',
            $rule,
        ));
    }
}
