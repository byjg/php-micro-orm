<?php

namespace Tests;

use ByJG\AnyDataset\Db\Interfaces\DbDriverInterface;
use Override;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[RequiresPhpExtension('pdo_mysql')]
class MysqlRepositoryUuidKeyTest extends RepositoryUuidKeyTestCase
{
    #[Override]
    protected function createDriver(): DbDriverInterface
    {
        return ConnectionUtil::getConnection('testmicroorm');
    }

    #[Override]
    protected function uuidColumnType(): string
    {
        return 'binary(16)';
    }

    #[Override]
    protected function storedValueSql(string $column): string
    {
        return 'concat(length(' . $column . '), \':\', hex(' . $column . '))';
    }

    #[Override]
    protected function expectedStoredValue(string $uuid): string
    {
        return '16:' . str_replace('-', '', $uuid);
    }
}
