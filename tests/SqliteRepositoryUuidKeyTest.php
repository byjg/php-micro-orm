<?php

namespace Tests;

use ByJG\AnyDataset\Db\Factory;
use ByJG\AnyDataset\Db\Interfaces\DbDriverInterface;
use Override;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[RequiresPhpExtension('pdo_sqlite')]
class SqliteRepositoryUuidKeyTest extends RepositoryUuidKeyTestCase
{
    #[Override]
    protected function createDriver(): DbDriverInterface
    {
        return Factory::getDbInstance('sqlite://' . sys_get_temp_dir() . '/micro-orm-uuidkey.db');
    }

    #[Override]
    protected function uuidColumnType(): string
    {
        return 'binary(16)';
    }

    #[Override]
    protected function storedValueSql(string $column): string
    {
        return 'typeof(' . $column . ') || \':\' || length(' . $column . ') || \':\' || hex(' . $column . ')';
    }

    #[Override]
    protected function expectedStoredValue(string $uuid): string
    {
        return 'blob:16:' . str_replace('-', '', $uuid);
    }
}
