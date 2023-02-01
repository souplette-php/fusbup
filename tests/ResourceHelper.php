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

    public static function tmp(string $name, callable $fn): void
    {
        if (!\is_dir($dir = __DIR__ . '/Resources/tmp')) {
            mkdir($dir, recursive: true);
        }
        if (false !== $tmp = tempnam($dir, $name)) {
            try {
                $fn($tmp);
            } finally {
                unlink($tmp);
            }
        }
    }
}
