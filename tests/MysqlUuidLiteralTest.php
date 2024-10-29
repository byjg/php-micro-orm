<?php

namespace Tests;

use ByJG\MicroOrm\Literal\MySqlUuidLiteral;
use ByJG\MicroOrm\Literal\SqliteUuidLiteral;

class MysqlUuidLiteralTest extends \Tests\HexUuidLiteralTest
{
    public function setUp(): void
    {
        $this->class = MySqlUuidLiteral::class;
    }
}