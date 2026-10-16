<?php

namespace Tests;

use ByJG\AnyDataset\Db\Factory;
use ByJG\AnyDataset\Db\Interfaces\DbDriverInterface;
use Override;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[RequiresPhpExtension('pdo_pgsql')]
class PostgresRepositoryTimestampsTest extends RepositoryTimestampsTestCase
{
    #[Override]
    protected function createDriver(): DbDriverInterface
    {
        return Factory::getDbInstance('pgsql://postgres:password@' . (getenv('PSQL_TEST_HOST') ?: '127.0.0.1'));
    }

    #[Override]
    protected function idColumn(): string
    {
        return 'id serial PRIMARY KEY';
    }

    #[Override]
    protected function dateTimeType(): string
    {
        return 'TIMESTAMP';
    }
}
