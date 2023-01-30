<?php declare(strict_types=1);

namespace ju1ius\FusBup\Loader;

use ju1ius\FusBup\Dafsa\Graph;
use ju1ius\FusBup\PslLoaderInterface;
use ju1ius\FusBup\PslLookupInterface;

final class DafsaFileLoader implements PslLoaderInterface
{
    public function __construct(
        private readonly string $filename = __DIR__ . '/../Resources/psl.dafsa',
    ) {
    }

    public function load(): PslLookupInterface
    {
        $fp = new \SplFileObject($this->filename, 'rb');
        if ($fp->fgets() !== Graph::HEADER) {
            throw new \RuntimeException(sprintf(
                'Not a DAFSA graph: %s',
                $this->filename,
            ));
        }

        $graph = $fp->fread($fp->getSize());

        return new Graph($graph);
    }
}
