<?php

namespace ByJG\MicroOrm\Literal;

class SqlServerUuidLiteral extends HexUuidLiteral
{
    public function __construct(Literal|string $value)
    {
        if ($value instanceof HexUuidLiteral) {
            $value = $value->formattedUuid;
        }

        $this->prefix = '0x';
        $this->suffix = '';
        parent::__construct($value);
    }
}
