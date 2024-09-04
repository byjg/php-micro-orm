<?php

namespace ByJG\MicroOrm\Literal;

interface LiteralInterface
{
    public function getLiteralValue(): mixed;

    public function setLiteralValue(mixed $literalValue): void;

    public function __toString(): string;
}