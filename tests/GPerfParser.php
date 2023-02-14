<?php declare(strict_types=1);

namespace Souplette\FusBup\Tests;

final class GPerfParser
{
    public static function parse(string $filename, bool $reverse = false): array
    {
        $words = [];
        foreach (self::iter($filename) as [$word, $flags]) {
            $words[] = match ($reverse) {
                true => strrev($word) . \chr($flags & 0x0F),
                false => $word . \chr($flags & 0x0F),
            };
        }

        return $words;
    }

    /**
     * @return iterable<array{string, int}>
     */
    public static function iter(string $filename): iterable
    {
        $file = new \SplFileObject($filename);
        $inSection = false;
        foreach ($file as $line) {
            if ($line === "%%\n") {
                $inSection = !$inSection;
                continue;
            }
            if (!$inSection) continue;
            $line = trim($line);
            if (!$line) continue;
            [$word, $flags] = preg_split('/,\s+/', $line, 2, \PREG_SPLIT_NO_EMPTY);
            yield [$word, (int)$flags];
        }
    }
}
