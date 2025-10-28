<?php

namespace ByJG\MicroOrm\Literal;

class MySqlUuidLiteral extends HexUuidLiteral
{
    public function __construct(Literal|string $value)
    {
        if ($value instanceof HexUuidLiteral) {
            $value = $value->formattedUuid;
        }

        parent::__construct($this->binaryString($value));
    }
}
