<?php

namespace Tests;

use ByJG\AnyDataset\Db\Factory;
use ByJG\AnyDataset\Db\Interfaces\DbDriverInterface;
use Override;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[RequiresPhpExtension('pdo_sqlsrv')]
class SqlServerSqlsrvRepositoryUuidKeyTest extends RepositoryUuidKeyTestCase
{
    #[Override]
    protected function createDriver(): DbDriverInterface
    {
        return Factory::getDbInstance('sqlsrv://sa:Pa55word@' . (getenv('MSSQL_TEST_HOST') ?: '127.0.0.1') . '/tempdb');
    }

    #[Override]
    protected function uuidColumnType(): string
    {
        return 'binary(16)';
    }

    #[Override]
    protected function storedValueSql(string $column): string
    {
        return 'concat(datalength(' . $column . '), \':\', convert(varchar(32), ' . $column . ', 2))';
    }

    #[Override]
    protected function expectedStoredValue(string $uuid): string
    {
        return '16:' . str_replace('-', '', $uuid);
    }
}
