<?php

namespace Tests;

use ByJG\AnyDataset\Db\Factory;
use ByJG\AnyDataset\Db\Interfaces\DbDriverInterface;
use Override;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[RequiresPhpExtension('pdo_sqlsrv')]
class SqlServerSqlsrvRepositoryTimestampsTest extends RepositoryTimestampsTestCase
{
    #[Override]
    protected function createDriver(): DbDriverInterface
    {
        return Factory::getDbInstance('sqlsrv://sa:Pa55word@' . (getenv('MSSQL_TEST_HOST') ?: '127.0.0.1') . '/tempdb');
    }

    #[Override]
    protected function idColumn(): string
    {
        return 'id INT IDENTITY(1,1) PRIMARY KEY';
    }

    #[Override]
    protected function dateTimeType(): string
    {
        return 'DATETIME2';
    }
}
