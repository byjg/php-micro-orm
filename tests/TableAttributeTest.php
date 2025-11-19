<?php

namespace Tests;

use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\AnyDataset\Db\Interfaces\DbDriverInterface;
use ByJG\AnyDataset\Db\Factory;
use ByJG\MicroOrm\InsertBulkQuery;
use ByJG\MicroOrm\Repository;
use ByJG\Util\Uri;
use Override;
use PHPUnit\Framework\TestCase;
use Tests\Model\ModelWithProcessors;

class TableAttributeTest extends TestCase
{
    const URI = 'sqlite:///tmp/test.db';

    protected DbDriverInterface $dbDriver;

    protected DatabaseExecutor $executor;

    #[Override]
    public function setUp(): void
    {
        $this->dbDriver = Factory::getDbInstance(self::URI);
        $this->executor = DatabaseExecutor::using($this->dbDriver);

        $this->executor->execute('create table users (
            id integer primary key autoincrement,
            name varchar(45),
            createdate datetime);'
        );
        $insertBulk = InsertBulkQuery::getInstance('users', ['name', 'createdate']);
        $insertBulk->values(['name' => 'John Doe', 'createdate' => '2015-05-02']);
        $insertBulk->values(['name' => 'Jane Doe', 'createdate' => '2017-01-04']);
        $insertBulk->buildAndExecute($this->executor);
    }

    #[Override]
    public function tearDown(): void
    {
        $uri = new Uri(self::URI);
        unlink($uri->getPath());
    }

    public function testModelWithTableAttributeAndProcessors()
    {
        $repository = new Repository($this->executor, ModelWithProcessors::class);

        // Test Insert with processor
        $model = new ModelWithProcessors();
        $model->name = 'Test User';
        $model->createdate = '2023-05-01';

        $repository->save($model);

        $savedModel = $repository->get($model->getId());

        // Verify processor was applied
        $this->assertEquals('Test User-processed', $savedModel->name);
        $this->assertEquals('2023-01-15', $savedModel->createdate);

        // Test Update with processor
        $savedModel->name = 'Updated User';
        $savedModel->createdate = '2023-06-01';

        $repository->save($savedModel);

        $updatedModel = $repository->get($savedModel->getId());

        // Verify processor was applied
        $this->assertEquals('Updated User-updated', $updatedModel->name);
        $this->assertEquals('2023-02-20', $updatedModel->createdate);
    }
} 