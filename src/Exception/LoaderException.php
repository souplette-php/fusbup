<?php declare(strict_types=1);

namespace Souplette\FusBup\Exception;

/**
 * An error happened while loading the compiled public suffix database.
 */
final class LoaderException extends \RuntimeException implements FusBupException
{
    public static function invalidDafsaHeader(string $filename): self
    {
        return new self(sprintf(
            'Invalid DAFSA header in %s',
            $filename,
        ));
    }
}
