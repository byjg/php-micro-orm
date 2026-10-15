<?php

namespace Tests;

use ByJG\AnyDataset\Db\Factory;
use ByJG\AnyDataset\Db\Interfaces\DbDriverInterface;
use Override;

class PostgresRepositoryUuidKeyTest extends RepositoryUuidKeyTestCase
{
    #[Override]
    protected function createDriver(): DbDriverInterface
    {
        return Factory::getDbInstance('pgsql://postgres:password@' . (getenv('PSQL_TEST_HOST') ?: '127.0.0.1'));
    }

    #[Override]
    protected function uuidColumnType(): string
    {
        return 'uuid';
    }

    #[Override]
    protected function storedValueSql(string $column): string
    {
        return 'pg_typeof(' . $column . ')::text || \':\' || ' . $column . '::text';
    }

    #[Override]
    protected function expectedStoredValue(string $uuid): string
    {
        return 'uuid:' . strtolower($uuid);
    }
}
