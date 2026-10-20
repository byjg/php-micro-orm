<?php

namespace ByJG\MicroOrm\Literal;

use ByJG\MicroOrm\Exception\InvalidArgumentException;
use Override;

/**
 * A UUID for a native PostgreSQL uuid column, written as the quoted text form
 * ('XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX'), which PostgreSQL converts to uuid.
 */
class PostgresUuidLiteral extends HexUuidLiteral
{
    /**
     * @throws InvalidArgumentException
     */
    #[Override]
    public function binaryString(LiteralInterface|string $value): string
    {
        parent::binaryString($value);
        return "'" . $this->formattedUuid . "'";
    }
}
