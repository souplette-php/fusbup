<?php declare(strict_types=1);

namespace ju1ius\FusBup\Loader;

use ju1ius\FusBup\Lookup\LookupInterface;

interface LoaderInterface
{
    public function load(): LookupInterface;
}
