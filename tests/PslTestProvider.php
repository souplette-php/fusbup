<?php declare(strict_types=1);

namespace Souplette\FusBup\Tests;

use JsonException;
use Traversable;

final class PslTestProvider
{
    /**
     * @throws JsonException
     */
    public static function isPublic(): Traversable
    {
        $i = 0;
        foreach (self::parse('is-etld.json') as [$input, $expected]) {
            if (str_starts_with($input, '.')) {
                continue;
            }
            $i++;
            $key = sprintf('#%d %s => %s', $i, $input, $expected ? 'true' : 'false');
            yield $key => [$input, $expected];
        }
    }

    /**
     * @return iterable<array{string, string}>
     * @throws JsonException
     */
    public static function unregistrable(): iterable
    {
        return self::parse('etld.json');
    }

    /**
     * @return iterable<array{string, string}>
     * @throws JsonException
     */
    public static function registrable(): iterable
    {
        return self::parse('etld+1.json');
    }

    /**
     * @throws JsonException
     */
    private static function parse(string $filename): array
    {
        return json_decode(
            file_get_contents(ResourceHelper::path($filename)),
            null,
            512,
            \JSON_THROW_ON_ERROR,
        );
    }
}
