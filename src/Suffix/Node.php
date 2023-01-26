<?php declare(strict_types=1);

namespace ju1ius\FusBup\Suffix;

use ju1ius\FusBup\Parser\Rule;

class Node
{

    public function __construct(
        public ?Rule $value = null,
        /**
         * @var array<string, Node>
         */
        public array $children = [],
    ) {
    }
}
