<?php

namespace ByJG\MicroOrm\Literal;

class SqliteUuidLiteral extends HexUuidLiteral
{
    public function __construct(Literal|string $value)
    {
        if ($value instanceof HexUuidLiteral) {
            $value = $value->formattedUuid;
        }

        parent::__construct($this->binaryString($value));
    }
}
