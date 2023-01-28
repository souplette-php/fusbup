<?php declare(strict_types=1);

namespace ju1ius\FusBup\Tests;

use Traversable;

final class PslTestFile
{
    public static function isPublic(): Traversable
    {
        foreach (self::parse('is-public.txt') as [$input, $expected]) {
            if (\is_null($input) || str_starts_with($input, '.')) {
                continue;
            }
            $expected = match ($expected) {
                '0' => false,
                '1' => true,
            };
            $key = "{$input} => {$expected}";
            yield $key => [$input, $expected];
        }
    }

    /**
     * @return Traversable<array{string, string}>
     */
    public static function unregistrable(): Traversable
    {
        yield from self::parse('unregisterable.txt');
    }

    /**
     * @return Traversable<array{string, string}>
     */
    public static function registrable(): Traversable
    {
        yield from self::parse('registerable.txt');
    }

    private static function parse(string $filename): Traversable
    {
        $file = new \SplFileObject(__DIR__ . '/Resources/' . $filename);
        foreach ($file as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '//')) {
                continue;
            }
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
