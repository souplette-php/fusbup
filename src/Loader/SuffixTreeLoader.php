<?php declare(strict_types=1);

namespace ju1ius\FusBup\Loader;

use ju1ius\FusBup\Lookup\LookupInterface;
use ju1ius\FusBup\Lookup\SuffixTree;

final class SuffixTreeLoader implements LoaderInterface
{
    public const DEFAULT_PATH = __DIR__ . '/../Resources/psl.php';

    public function __construct(
        private readonly string $filename = self::DEFAULT_PATH,
    ) {
    }

    public function load(): LookupInterface
    {
        return new SuffixTree(require $this->filename);
    }
}
