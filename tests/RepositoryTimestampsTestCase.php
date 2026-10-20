<?php

namespace Tests;

use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\AnyDataset\Db\Interfaces\DbDriverInterface;
use ByJG\MicroOrm\Repository;
use PHPUnit\Framework\TestCase;
use Tests\Model\Timestamps;

/**
 * created_at and updated_at (the CreatedAt and UpdatedAt traits) and deleted_at (a soft delete)
 * are written as CURRENT_TIMESTAMP, which must store a real date in a date column, run against
 * each database by the subclasses.
 */
abstract class RepositoryTimestampsTestCase extends TestCase
{
    protected DatabaseExecutor $executor;
    protected Repository $repository;

    abstract protected function createDriver(): DbDriverInterface;

    /**
     * The definition of an auto-increment integer primary key named id.
     */
    abstract protected function idColumn(): string;

    /**
     * The column type of a date and time.
     */
    abstract protected function dateTimeType(): string;

    public function setUp(): void
    {
        $this->executor = DatabaseExecutor::using($this->createDriver());
        $this->executor->execute('DROP TABLE IF EXISTS timestamps');
        $date = $this->dateTimeType();
        $this->executor->execute(
            'CREATE TABLE timestamps (' . $this->idColumn() . ', name VARCHAR(45), '
            . "created_at $date NULL, updated_at $date NULL, deleted_at $date NULL)"
        );
        $this->repository = new Repository($this->executor, Timestamps::class);
    }

    public function tearDown(): void
    {
        $this->executor->execute('DROP TABLE IF EXISTS timestamps');
    }

    /**
     * A date the database stored, which must be about now: the session time zone of the
     * database may differ from PHP's, so a day either way is accepted.
     */
    protected function assertIsAboutNow(mixed $value, string $column): void
    {
        $this->assertNotEmpty($value, "$column was not written");
        $time = strtotime((string)$value);
        $this->assertNotFalse($time, "$column is not a date: $value");
        $this->assertLessThan(86400 + 60, abs($time - time()), "$column is not about now: $value");
    }

    protected function stored(string $column): mixed
    {
        return $this->executor->getScalar("SELECT $column FROM timestamps WHERE name = 'row'");
    }

    public function testCreatedAtIsWrittenOnInsert(): void
    {
        $model = new Timestamps();
        $model->name = 'row';
        $this->repository->save($model);

        $this->assertIsAboutNow($this->stored('created_at'), 'created_at');
        $this->assertNull($this->stored('deleted_at'));
    }

    public function testUpdatedAtIsWrittenOnUpdate(): void
    {
        $model = new Timestamps();
        $model->name = 'row';
        $this->repository->save($model);

        $model = $this->repository->get($model->id);
        $this->repository->save($model);

        $this->assertIsAboutNow($this->stored('updated_at'), 'updated_at');
    }

    public function testASoftDeleteWritesDeletedAt(): void
    {
        $model = new Timestamps();
        $model->name = 'row';
        $this->repository->save($model);

        $this->assertTrue($this->repository->delete($model->id));

        $this->assertIsAboutNow($this->stored('deleted_at'), 'deleted_at');
        $this->assertEmpty($this->repository->get($model->id));
    }
}
