<?php declare(strict_types=1);

namespace ju1ius\FusBup\Tests;

use Traversable;

final class PslTestFile
{
    /**
     * @return Traversable<array{string, string}>
     */
    public static function unregisterable(): Traversable
    {
        yield from self::parse('unregisterable.txt');
    }

    /**
     * @return Traversable<array{string, string}>
     */
    public static function registerable(): Traversable
    {
        yield from self::parse('registerable.txt');
    }

    private static function parse(string $filename): Traversable
    {
        $file = new \SplFileObject(__DIR__ . '/Resources/' . $filename);
        $id = 0;
        foreach ($file as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '//')) {
                continue;
            }
            $id++;
            [$input, $expected] = explode(' ', $line, 2);
            yield [
                self::normalizeValue($input),
                self::normalizeValue($expected),
            ];
        }
    }

    private static function normalizeValue(string $value): ?string
    {
        return match ($value) {
            'null' => null,
            default => $value,
        };
    }
}
