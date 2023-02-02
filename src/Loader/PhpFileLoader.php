<?php declare(strict_types=1);

namespace ju1ius\FusBup\Loader;

use ju1ius\FusBup\Lookup\PslLookupInterface;
use ju1ius\FusBup\Lookup\SuffixTree;

final class PhpFileLoader implements LoaderInterface
{
    public function __construct(
        private readonly string $filename = __DIR__ . '/../Resources/psl.php',
    ) {
    }

    public function load(): PslLookupInterface
    {
        return new SuffixTree(require $this->filename);
    }
}
