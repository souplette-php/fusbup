<?php declare(strict_types=1);

namespace ju1ius\FusBup\Utils;

use ju1ius\FusBup\Exception\IdnException;

/**
 * @link https://url.spec.whatwg.org/#idna
 */
final class Idn
{
    private const TO_ASCII_FLAGS = \IDNA_USE_STD3_RULES|\IDNA_CHECK_BIDI|\IDNA_NONTRANSITIONAL_TO_ASCII;
    private const TO_UTF8_FLAGS = \IDNA_USE_STD3_RULES|\IDNA_CHECK_BIDI|\IDNA_NONTRANSITIONAL_TO_UNICODE;

    /**
     * @param string[]|string $domain
     * @return string
     */
    public static function toAscii(array|string $domain): string
    {
        if (\is_array($domain)) {
            $domain = implode('.', $domain);
        }
        $idn = idn_to_ascii($domain, self::TO_ASCII_FLAGS, \INTL_IDNA_VARIANT_UTS46, $info);
        if ($idn === false) {
            throw IdnException::toAscii($domain, $info);
        }
        return $idn;
    }

    /**
     * @param string[]|string $domain
     * @return string
     */
    public static function toUnicode(array|string $domain): string
    {
        if (\is_array($domain)) {
            $domain = implode('.', $domain);
        }
        $idn = idn_to_utf8($domain, self::TO_UTF8_FLAGS, \INTL_IDNA_VARIANT_UTS46, $info);
        if ($idn === false) {
            throw IdnException::toUnicode($domain, $info);
        }
        return $idn;
    }
}
