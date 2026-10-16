<?php

namespace Tests;

use ByJG\AnyDataset\Db\Factory;
use ByJG\AnyDataset\Db\Interfaces\DbDriverInterface;
use Override;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[RequiresPhpExtension('pdo_sqlite')]
class SqliteRepositoryTimestampsTest extends RepositoryTimestampsTestCase
{
    #[Override]
    protected function createDriver(): DbDriverInterface
    {
        return Factory::getDbInstance('sqlite://' . sys_get_temp_dir() . '/micro-orm-timestamps.db');
    }

    #[Override]
    protected function idColumn(): string
    {
        return 'id INTEGER PRIMARY KEY AUTOINCREMENT';
    }

    #[Override]
    protected function dateTimeType(): string
    {
        return 'DATETIME';
    }
}
