<?php declare(strict_types=1);

namespace Souplette\FusBup\Loader;

use Souplette\FusBup\Lookup\LookupInterface;

interface LoaderInterface
{
    public function load(): LookupInterface;
}
