<?php declare(strict_types=1);

namespace ju1ius\FusBup\Exception;

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
