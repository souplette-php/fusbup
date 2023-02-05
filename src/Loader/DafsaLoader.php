<?php declare(strict_types=1);

namespace ju1ius\FusBup\Loader;

use ju1ius\FusBup\Exception\LoaderException;
use ju1ius\FusBup\Lookup\Dafsa;
use ju1ius\FusBup\Lookup\LookupInterface;

final class DafsaLoader implements LoaderInterface
{
    public const DEFAULT_PATH = __DIR__ . '/../Resources/psl.dafsa';

    public function __construct(
        private readonly string $filename = self::DEFAULT_PATH,
    ) {
    }

    public function load(): LookupInterface
    {
        $fp = new \SplFileObject($this->filename, 'rb');
        if ($fp->fgets() !== Dafsa::HEADER) {
            throw LoaderException::invalidDafsaHeader($this->filename);
        }

        $graph = $fp->fread($fp->getSize());

        return new Dafsa($graph);
    }
}
