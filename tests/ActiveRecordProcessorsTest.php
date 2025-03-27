<?php

namespace Tests;

use ByJG\AnyDataset\Db\Factory;
use ByJG\MicroOrm\InsertBulkQuery;
use ByJG\MicroOrm\ORM;
use ByJG\Util\Uri;
use Override;
use PHPUnit\Framework\TestCase;
use Tests\Model\ActiveRecordWithProcessors;

class ActiveRecordProcessorsTest extends TestCase
{
    const URI = 'sqlite:///tmp/test.db';

    protected $dbDriver;

    #[Override]
    public function setUp(): void
    {
        $this->dbDriver = Factory::getDbInstance(self::URI);

        $this->dbDriver->execute('create table users (
            id integer primary key autoincrement,
            name varchar(45),
            createdate datetime);'
        );
        $insertBulk = InsertBulkQuery::getInstance('users', ['name', 'createdate']);
        $insertBulk->values(['name' => 'John Doe', 'createdate' => '2015-05-02']);
        $insertBulk->values(['name' => 'Jane Doe', 'createdate' => '2017-01-04']);
        $insertBulk->buildAndExecute($this->dbDriver);

        ORM::defaultDbDriver($this->dbDriver);
        ActiveRecordWithProcessors::reset();
    }

    #[Override]
    public function tearDown(): void
    {
        ORM::defaultDbDriver(null);
        $uri = new Uri(self::URI);
        unlink($uri->getPath());
    }

    public function testActiveRecordWithProcessors()
    {
        // Test Insert with processor
        $model = ActiveRecordWithProcessors::new();
        $model->name = 'Test User';
        $model->createdate = '2023-05-01';

        $model->save();

        $savedModel = ActiveRecordWithProcessors::get($model->getId());

        // Verify processor was applied
        $this->assertEquals('Test User-processed', $savedModel->name);
        $this->assertEquals('2023-01-15', $savedModel->createdate);

        // Test Update with processor
        $savedModel->name = 'Updated User';
        $savedModel->createdate = '2023-06-01';

        $savedModel->save();

        $updatedModel = ActiveRecordWithProcessors::get($savedModel->getId());

        // Verify processor was applied
        $this->assertEquals('Updated User-updated', $updatedModel->name);
        $this->assertEquals('2023-02-20', $updatedModel->createdate);
    }
} 