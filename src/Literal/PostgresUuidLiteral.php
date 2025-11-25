<?php

namespace ByJG\MicroOrm\Literal;

class PostgresUuidLiteral extends HexUuidLiteral
{
    public function __construct(LiteralInterface|string $value)
    {
        if ($value instanceof HexUuidLiteral) {
            $value = $value->formattedUuid;
        }

        $this->prefix = '\\x';
        $this->suffix = '';
        parent::__construct($value);
    }
}
