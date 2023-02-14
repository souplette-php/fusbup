<?php declare(strict_types=1);

namespace Souplette\FusBup\Compiler\Utils;

use Souplette\FusBup\Lookup\Dafsa;

/**
 * @internal
 */
final class ByteArray
{
    public static function fromString(string $bytes): array
    {
        return array_map(\ord(...), str_split($bytes));
    }

    public static function fromDafsa(string $dafsa): array
    {
        $dafsa = substr($dafsa, \strlen(Dafsa::HEADER));
        return self::fromString($dafsa);
    }

    public static function toString(array $bytes): string
    {
        return implode('', array_map(chr(...), $bytes));
    }

    public static function toDafsa(array $bytes): string
    {
        return Dafsa::HEADER . self::toString($bytes);
    }
}
