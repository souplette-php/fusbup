<?php declare(strict_types=1);

namespace ju1ius\FusBup\Parser;

enum Section
{
    case None;
    case Icann;
    case Private;
    case Unknown;
}
