<?php declare(strict_types=1);

namespace ju1ius\FusBup;

interface PslLoaderInterface
{
    public function load(): PslLookupInterface;
}
