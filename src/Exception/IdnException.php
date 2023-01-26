<?php declare(strict_types=1);

namespace ju1ius\FusBup\Exception;

final class IdnException extends \RuntimeException implements FusBupException
{
    public static function toAscii(string $input, array $info): self
    {
        return new self(sprintf(
            'Could not convert domain "%s" to ASCII: %s.',
            $input,
            implode(', ', self::getErrors($info)),
        ));
    }

    public static function toUnicode(string $input, array $info): self
    {
        return new self(sprintf(
            'Could not convert domain "%s" to Unicode: %s.',
            $input,
            implode(', ', self::getErrors($info)),
        ));
    }

    private const ERRORS = [
        IDNA_ERROR_EMPTY_LABEL => 'empty label',
        IDNA_ERROR_LABEL_TOO_LONG => 'label too long',
        IDNA_ERROR_DOMAIN_NAME_TOO_LONG => 'domain name too long',
        IDNA_ERROR_LEADING_HYPHEN => 'leading hyphen',
        IDNA_ERROR_TRAILING_HYPHEN => 'trailing hyphen',
        IDNA_ERROR_HYPHEN_3_4 => 'hyphen in 3rd and 4th position',
        IDNA_ERROR_LEADING_COMBINING_MARK => 'leading combining mark',
        IDNA_ERROR_DISALLOWED => 'disallowed codepoint',
        IDNA_ERROR_PUNYCODE => 'invalid punycode',
        IDNA_ERROR_LABEL_HAS_DOT => 'label contains dot',
        IDNA_ERROR_INVALID_ACE_LABEL => 'invalid ace label',
        IDNA_ERROR_BIDI => 'invalid bidi label',
        IDNA_ERROR_CONTEXTJ => 'invalid context joiner',
    ];

    private static function getErrors(array $info): array
    {
        $mask = $info['errors'] ?? 0;
        $errors = [];
        foreach (self::ERRORS as $bit => $message) {
            if ($mask & $bit) {
                $errors[] = $message;
            }
        }
        return $errors;
    }
}
