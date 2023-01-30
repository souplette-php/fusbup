<?php declare(strict_types=1);

namespace ju1ius\FusBup\Tests;

final class ResourceHelper
{
    public static function path(string $relativePath): string
    {
        return __DIR__ . '/Resources/' . ltrim($relativePath, '/');
    }

    public static function glob(string $relativePath): array
    {
        return glob(self::path($relativePath));
    }
}
