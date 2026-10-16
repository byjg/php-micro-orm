<?php

namespace Tests;

use ByJG\AnyDataset\Db\Interfaces\DbDriverInterface;
use Override;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[RequiresPhpExtension('pdo_mysql')]
class MysqlRepositoryTimestampsTest extends RepositoryTimestampsTestCase
{
    #[Override]
    protected function createDriver(): DbDriverInterface
    {
        return ConnectionUtil::getConnection('testmicroorm');
    }

    #[Override]
    protected function idColumn(): string
    {
        return 'id INTEGER AUTO_INCREMENT PRIMARY KEY';
    }

    #[Override]
    protected function dateTimeType(): string
    {
        return 'DATETIME';
    }
}
