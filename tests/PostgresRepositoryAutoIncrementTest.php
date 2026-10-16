<?php

namespace Tests;

use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\AnyDataset\Db\Factory;
use ByJG\MicroOrm\Repository;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Tests\Model\AutoIncrementKey;

/**
 * An auto-increment (serial) primary key on PostgreSQL, which, unlike MySQL and SQLite, rejects
 * an explicit NULL for it: the key of a new instance must be left out of the INSERT.
 */
#[RequiresPhpExtension('pdo_pgsql')]
class PostgresRepositoryAutoIncrementTest extends TestCase
{
    protected DatabaseExecutor $executor;
    protected Repository $repository;

    public function setUp(): void
    {
        $this->executor = DatabaseExecutor::using(
            Factory::getDbInstance('pgsql://postgres:password@' . (getenv('PSQL_TEST_HOST') ?: '127.0.0.1'))
        );
        $this->executor->execute('DROP TABLE IF EXISTS autoincrementkey');
        $this->executor->execute('CREATE TABLE autoincrementkey (id serial PRIMARY KEY, name VARCHAR(45))');
        $this->repository = new Repository($this->executor, AutoIncrementKey::class);
    }

    public function tearDown(): void
    {
        $this->executor->execute('DROP TABLE IF EXISTS autoincrementkey');
    }

    public function testANewInstanceGetsTheKeyTheDatabaseGenerates(): void
    {
        foreach (['first', 'second'] as $name) {
            $model = new AutoIncrementKey();
            $model->name = $name;
            $this->repository->save($model);
        }

        $this->assertSame(2, $model->id);
        $this->assertSame('first', $this->repository->get(1)->name);
        $this->assertSame('second', $this->repository->get(2)->name);
    }
}
