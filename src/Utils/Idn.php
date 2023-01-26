<?php declare(strict_types=1);

namespace ju1ius\FusBup\Utils;

use ju1ius\FusBup\Exception\IdnException;

final class Idn
{
    /**
     * @param string[]|string $domain
     * @return string
     */
    public static function toAscii(array|string $domain): string
    {
        if (\is_array($domain)) {
            $domain = implode('.', $domain);
        }
        $idn = idn_to_ascii($domain, \IDNA_NONTRANSITIONAL_TO_ASCII, \INTL_IDNA_VARIANT_UTS46, $info);
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
        $idn = idn_to_utf8($domain, \IDNA_NONTRANSITIONAL_TO_UNICODE, \INTL_IDNA_VARIANT_UTS46, $info);
        if ($idn === false) {
            throw IdnException::toUnicode($domain, $info);
        }
        return $idn;
    }
}
