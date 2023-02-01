<?php declare(strict_types=1);

namespace ju1ius\FusBup\Compiler\Utils;

use ju1ius\FusBup\Dafsa\Graph;

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
        $dafsa = substr($dafsa, \strlen(Graph::HEADER));
        return self::fromString($dafsa);
    }

    public static function toString(array $bytes): string
    {
        return implode('', array_map(chr(...), $bytes));
    }

    public static function toDafsa(array $bytes): string
    {
        return Graph::HEADER . self::toString($bytes);
    }
}
