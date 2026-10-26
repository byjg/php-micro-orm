<?php

namespace Tests;

use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\AnyDataset\Db\Factory;
use ByJG\MicroOrm\Exception\InvalidArgumentException as OrmInvalidArgumentException;
use ByJG\MicroOrm\Interface\EntityValidatorInterface;
use ByJG\MicroOrm\ORM;
use ByJG\MicroOrm\Repository;
use ByJG\Util\Uri;
use InvalidArgumentException;
use Override;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Tests\Model\ActiveRecordWithValidator;
use Tests\Model\ModelWithProcessors;
use Tests\Model\RecordingValidator;
use Tests\Model\TestInsertProcessor;
use Tests\Model\UuidKeyWithValidator;

#[RequiresPhpExtension('pdo_sqlite')]
class EntityValidatorTest extends TestCase
{
    const URI = 'sqlite:///tmp/micro-orm-validator.db';

    protected DatabaseExecutor $executor;

    #[Override]
    public function setUp(): void
    {
        $this->executor = DatabaseExecutor::using(Factory::getDbInstance(self::URI));
        $this->executor->execute('create table users (id integer primary key autoincrement, name varchar(45), createdate datetime)');
        $this->executor->execute('create table uuidkey (id binary(16) primary key, name varchar(45), ref binary(16))');

        ORM::defaultDbDriver($this->executor);
        ActiveRecordWithValidator::reset();
        RecordingValidator::$calls = [];
    }

    #[Override]
    public function tearDown(): void
    {
        ORM::resetMemory();
        unlink((new Uri(self::URI))->getPath());
    }

    public function testTheValidatorReceivesTheInstanceOnInsertAndOnUpdate(): void
    {
        $model = ActiveRecordWithValidator::new(['name' => 'John']);
        $model->save();

        $model->name = 'John Doe';
        $model->save();

        $this->assertCount(2, RecordingValidator::$calls);
        $this->assertSame($model, RecordingValidator::$calls[0]['instance']);
        $this->assertTrue(RecordingValidator::$calls[0]['isInsert']);
        $this->assertSame($model, RecordingValidator::$calls[1]['instance']);
        $this->assertFalse(RecordingValidator::$calls[1]['isInsert']);
        $this->assertSame('John Doe', ActiveRecordWithValidator::get($model->getId())->name);
    }

    public function testAnInvalidInstanceIsNotInserted(): void
    {
        try {
            ActiveRecordWithValidator::new(['name' => ''])->save();
            $this->fail('The empty name was saved');
        } catch (InvalidArgumentException $ex) {
            $this->assertSame('The name is empty', $ex->getMessage());
        }

        $this->assertSame(0, $this->executor->getScalar('select count(*) from users'));
    }

    public function testAnInvalidInstanceIsNotUpdated(): void
    {
        $model = ActiveRecordWithValidator::new(['name' => 'John']);
        $model->save();

        $model->name = '';
        try {
            $model->save();
            $this->fail('The empty name was saved');
        } catch (InvalidArgumentException) {
        }

        $this->assertSame('John', ActiveRecordWithValidator::get($model->getId())->name);
    }

    public function testTheValidatorSeesTheValuesOfTheModelNotOfTheDatabase(): void
    {
        $repository = new Repository($this->executor, UuidKeyWithValidator::class);

        $model = new UuidKeyWithValidator();
        $model->name = 'John';
        $repository->save($model);

        $saved = $repository->get($model->id);
        $saved->name = 'John Doe';
        $repository->save($saved);

        // On insert the key is still empty: it is generated after the validation.
        $this->assertNull(RecordingValidator::$calls[0]['id']);
        // On update the key is the UUID string of the model, not the binary value written to the column.
        $this->assertMatchesRegularExpression('/^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}$/i', RecordingValidator::$calls[1]['id']);
    }

    public function testAValidatorCanBeSetOnTheRepository(): void
    {
        $repository = new Repository($this->executor, ModelWithProcessors::class);
        $repository->setValidator(new class implements EntityValidatorInterface {
            public function validate(mixed $instance, bool $isInsert): void
            {
                throw new InvalidArgumentException('Rejected');
            }
        });

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Rejected');
        $model = new ModelWithProcessors();
        $model->name = 'John';
        $repository->save($model);
    }

    public function testAClassThatIsNotAValidatorIsRefused(): void
    {
        $repository = new Repository($this->executor, ModelWithProcessors::class);

        $this->expectException(OrmInvalidArgumentException::class);
        $this->expectExceptionMessage('must implement EntityValidatorInterface');
        $repository->setValidator(TestInsertProcessor::class);
    }
}
