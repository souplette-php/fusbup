<?php declare(strict_types=1);

namespace ju1ius\FusBup\Loader;

use ju1ius\FusBup\Exception\LoaderException;
use ju1ius\FusBup\Lookup\Dafsa;
use ju1ius\FusBup\Lookup\PslLookupInterface;

final class DafsaFileLoader implements LoaderInterface
{
    public function __construct(
        private readonly string $filename = __DIR__ . '/../Resources/psl.dafsa',
    ) {
    }

    public function load(): PslLookupInterface
    {
        $fp = new \SplFileObject($this->filename, 'rb');
        if ($fp->fgets() !== Dafsa::HEADER) {
            throw LoaderException::invalidDafsaHeader($this->filename);
        }

        $graph = $fp->fread($fp->getSize());

        return new Dafsa($graph);
    }
}
