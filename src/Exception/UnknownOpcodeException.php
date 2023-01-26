<?php declare(strict_types=1);

namespace ju1ius\FusBup\Exception;

final class UnknownOpcodeException extends \LogicException implements FusBupException
{
    public function __construct(int $opcode)
    {
        parent::__construct("Unknown opcode: {$opcode}.");
    }
}
