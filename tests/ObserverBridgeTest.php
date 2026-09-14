<?php

namespace Tests;

use ByJG\AnyDataset\Db\DatabaseEvent;
use ByJG\AnyDataset\Db\DatabaseEventTypeEnum;
use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\AnyDataset\Db\Interfaces\DatabaseEventObserverInterface;
use ByJG\MicroOrm\DeleteQuery;
use ByJG\MicroOrm\Enum\ObserverEvent;
use ByJG\MicroOrm\InsertQuery;
use ByJG\MicroOrm\Interface\ObserverProcessorInterface;
use ByJG\MicroOrm\Interface\StatementHookInterface;
use ByJG\MicroOrm\Mapper;
use ByJG\MicroOrm\ObserverData;
use ByJG\MicroOrm\ORM;
use ByJG\MicroOrm\OrmSqlStatement;
use ByJG\MicroOrm\Repository;
use ByJG\MicroOrm\UpdateQuery;
use Exception;
use Override;
use PHPUnit\Framework\TestCase;
use Tests\Model\ModelWithAttributes;
use Tests\Model\Users;
use Throwable;

/**
 * Records every ObserverData it receives, for assertions.
 */
class RecordingObserver implements ObserverProcessorInterface
{
    /** @var ObserverData[] */
    public array $received = [];

    /** @var Throwable[] */
    public array $errors = [];

    public function __construct(protected string $table)
    {
    }

    #[Override]
    public function process(ObserverData $observerData): void
    {
        $this->received[] = $observerData;
    }

    #[Override]
    public function onError(Throwable $exception, ObserverData $observerData): void
    {
        $this->errors[] = $exception;
    }

    #[Override]
    public function getObservedTable(): string
    {
        return $this->table;
    }
}

/**
 * A processor that also hooks BEFORE_EXECUTE and can veto the write.
 */
class VetoObserver extends RecordingObserver implements StatementHookInterface
{
    /** @var OrmSqlStatement[] */
    public array $beforeStatements = [];

    public bool $veto = false;

    #[Override]
    public function beforeStatement(OrmSqlStatement $statement, Repository $repository): void
    {
        $this->beforeStatements[] = $statement;
        if ($this->veto) {
            throw new Exception("Vetoed by observer");
        }
    }
}

/**
 * Raw anydataset-db observer, attached through Repository::addObserver().
 */
class RawRecordingObserver implements DatabaseEventObserverInterface
{
    /** @var DatabaseEvent[] */
    public array $events = [];

    #[Override]
    public function subscribedEvents(): array
    {
        return [DatabaseEventTypeEnum::BEFORE_EXECUTE, DatabaseEventTypeEnum::AFTER_EXECUTE];
    }

    #[Override]
    public function handleEvent(DatabaseEvent $event): void
    {
        $this->events[] = $event;
    }
}

class ObserverBridgeTest extends TestCase
{
    protected Repository $userRepository;
    protected Mapper $userMapper;
    protected DatabaseExecutor $executor;

    #[Override]
    public function setUp(): void
    {
        $dbDriver = ConnectionUtil::getConnection("testmicroorm_observer");
        $this->executor = DatabaseExecutor::using($dbDriver);
        $this->userMapper = new Mapper(Users::class, 'users', 'Id');
        $this->userRepository = new Repository($this->executor, $this->userMapper);

        $this->executor->execute('create table users (
            id integer primary key  auto_increment,
            name varchar(45),
            createdate datetime);'
        );
        $this->executor->execute("insert into users (name, createdate) values ('John Doe', '2015-05-02')");
        $this->executor->execute("insert into users (name, createdate) values ('Jane Doe', '2017-01-04')");

        $this->executor->execute('create table info (
            id integer primary key  auto_increment,
            iduser INTEGER,
            property decimal(10, 2),
            registration_id VARCHAR(255) not null,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at datetime);'
        );
        $this->executor->execute("insert into info (iduser, property, registration_id) values (1, 30.4, 'REG001')");
    }

    #[Override]
    public function tearDown(): void
    {
        $this->executor->execute('drop table if exists users;');
        $this->executor->execute('drop table if exists info;');
        ORM::resetMemory();
    }

    public function testSoftDeleteFiresSoftDeleteEvent()
    {
        $infoRepository = new Repository($this->executor, ModelWithAttributes::class);

        $observer = new RecordingObserver('info');
        $infoRepository->addObserver($observer);

        $infoRepository->delete(1);

        $this->assertCount(1, $observer->received);
        $event = $observer->received[0];
        $this->assertEquals(ObserverEvent::SoftDelete, $event->getEvent());
        $this->assertEquals('info', $event->getTable());
        $this->assertNull($event->getData());
        $this->assertEquals(['pkid' => 1], $event->getOldData());

        // soft delete: the row still exists, with deleted_at set
        $deletedAt = $this->executor->getScalar('select deleted_at from info where id = 1');
        $this->assertNotNull($deletedAt);
    }

    public function testDirectExecutorWriteFiresObserver()
    {
        $observer = new RecordingObserver('users');
        $this->userRepository->addObserver($observer);

        // a built statement executed directly on the executor, bypassing the repository
        $insert = InsertQuery::getInstance('users', ['name' => 'Direct', 'createdate' => '2024-01-01'])->build();
        $this->executor->execute($insert);

        $update = UpdateQuery::getInstance()
            ->table('users')
            ->set('name', 'DirectChanged')
            ->where('name = :name', ['name' => 'Direct'])
            ->build();
        $this->executor->execute($update);

        $delete = DeleteQuery::getInstance()
            ->table('users')
            ->where('name = :name', ['name' => 'DirectChanged'])
            ->build();
        $this->executor->execute($delete);

        $this->assertCount(3, $observer->received);
        $this->assertEquals(ObserverEvent::Insert, $observer->received[0]->getEvent());
        // no entity is available outside save(): the params are provided instead
        $this->assertEquals(['name' => 'Direct', 'createdate' => '2024-01-01'], $observer->received[0]->getData());
        $this->assertEquals(ObserverEvent::Update, $observer->received[1]->getEvent());
        $this->assertEquals(ObserverEvent::Delete, $observer->received[2]->getEvent());
        $this->assertNull($observer->received[2]->getData());
        $this->assertEquals(['name' => 'DirectChanged'], $observer->received[2]->getOldData());
    }

    public function testBulkExecuteFiresObserversAfterCommit()
    {
        $observer = new RecordingObserver('users');
        $this->userRepository->addObserver($observer);

        $this->userRepository->bulkExecute([
            InsertQuery::getInstance('users', ['name' => 'Bulk1', 'createdate' => '2024-01-01']),
            UpdateQuery::getInstance()
                ->table('users')
                ->set('name', 'Bulk2')
                ->where('name = :name', ['name' => 'Bulk1']),
        ]);

        $this->assertCount(2, $observer->received);
        $this->assertEquals(ObserverEvent::Insert, $observer->received[0]->getEvent());
        $this->assertEquals(ObserverEvent::Update, $observer->received[1]->getEvent());
    }

    public function testBulkExecuteRollbackFiresNothing()
    {
        $observer = new RecordingObserver('users');
        $this->userRepository->addObserver($observer);

        $exceptionThrown = false;
        try {
            $this->userRepository->bulkExecute([
                InsertQuery::getInstance('users', ['name' => 'BulkFail', 'createdate' => '2024-01-01']),
                InsertQuery::getInstance('users', ['nonexistent_column' => 'x']),
            ]);
        } catch (Exception $ex) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown);
        $this->assertCount(0, $observer->received);
        $this->assertEquals(0, $this->executor->getScalar("select count(*) from users where name = 'BulkFail'"));
    }

    public function testObserverScopingIsPerExecutor()
    {
        $observer = new RecordingObserver('users');
        $this->userRepository->addObserver($observer);

        // another executor over the same database: the observer must NOT fire
        $otherExecutor = DatabaseExecutor::using(ConnectionUtil::getConnection("testmicroorm_observer"));
        $otherRepository = new Repository($otherExecutor, $this->userMapper);
        $users = new Users();
        $users->setName('OtherConnection');
        $users->setCreatedate('2024-01-01');
        $otherRepository->save($users);

        $this->assertCount(0, $observer->received);

        // a repository sharing the same executor: the observer fires
        $sharedRepository = new Repository($this->executor, $this->userMapper);
        $users = new Users();
        $users->setName('SharedConnection');
        $users->setCreatedate('2024-01-01');
        $sharedRepository->save($users);

        $this->assertCount(1, $observer->received);
        $this->assertEquals(ObserverEvent::Insert, $observer->received[0]->getEvent());
    }

    public function testStatementHookReceivesSqlAndCanVeto()
    {
        $observer = new VetoObserver('users');
        $this->userRepository->addObserver($observer);

        // hook receives the statement before it executes
        $users = new Users();
        $users->setName('HookTest');
        $users->setCreatedate('2024-01-01');
        $this->userRepository->save($users);

        $this->assertCount(1, $observer->beforeStatements);
        $this->assertStringContainsString('INSERT INTO `users`', $observer->beforeStatements[0]->getSql());
        $this->assertCount(1, $observer->received);

        // a throwing hook vetoes the write: the row must not be persisted and process() must not fire
        $observer->veto = true;
        $users = new Users();
        $users->setName('Vetoed');
        $users->setCreatedate('2024-01-01');

        $exceptionThrown = false;
        try {
            $this->userRepository->save($users);
        } catch (Exception $ex) {
            $exceptionThrown = true;
            $this->assertEquals("Vetoed by observer", $ex->getMessage());
        }

        $this->assertTrue($exceptionThrown);
        $this->assertEquals(0, $this->executor->getScalar("select count(*) from users where name = 'Vetoed'"));
        $this->assertCount(1, $observer->received);
    }

    public function testObserverDataCarriesStatement()
    {
        $observer = new RecordingObserver('users');
        $this->userRepository->addObserver($observer);

        $users = new Users();
        $users->setName('WithStatement');
        $users->setCreatedate('2024-01-01');
        $this->userRepository->save($users);

        $this->assertCount(1, $observer->received);
        $statement = $observer->received[0]->getStatement();
        $this->assertInstanceOf(OrmSqlStatement::class, $statement);
        $this->assertStringContainsString('INSERT INTO `users`', $statement->getSql());
        $this->assertEquals('WithStatement', $statement->getParams()['name']);
        // deferred dispatch: the entity is rehydrated before the observer runs
        $this->assertNotEmpty($observer->received[0]->getData()->getId());
    }

    public function testRawObserverPassthrough()
    {
        $rawObserver = new RawRecordingObserver();
        $this->userRepository->addObserver($rawObserver);

        $users = new Users();
        $users->setName('RawObserved');
        $users->setCreatedate('2024-01-01');
        $this->userRepository->save($users);

        $this->assertNotEmpty($rawObserver->events);
        $types = array_map(fn($event) => $event->getType(), $rawObserver->events);
        $this->assertContains(DatabaseEventTypeEnum::BEFORE_EXECUTE, $types);
        $this->assertContains(DatabaseEventTypeEnum::AFTER_EXECUTE, $types);
    }

    public function testAddDbDriverForWriteAttachesObservers()
    {
        $observer = new RecordingObserver('users');
        $this->userRepository->addObserver($observer);

        // writes switch to a new executor after the observer was registered
        $writeExecutor = DatabaseExecutor::using(ConnectionUtil::getConnection("testmicroorm_observer"));
        $this->userRepository->addDbDriverForWrite($writeExecutor);

        $users = new Users();
        $users->setName('WriteExecutor');
        $users->setCreatedate('2024-01-01');
        $this->userRepository->save($users);

        $this->assertCount(1, $observer->received);
        $this->assertEquals(ObserverEvent::Insert, $observer->received[0]->getEvent());
    }

    public function testOnErrorStillSwallowsProcessExceptions()
    {
        $observer = new class('users') extends RecordingObserver {
            #[Override]
            public function process(ObserverData $observerData): void
            {
                parent::process($observerData);
                throw new Exception("Observer failure");
            }
        };
        $this->userRepository->addObserver($observer);

        $users = new Users();
        $users->setName('ErrorSwallowed');
        $users->setCreatedate('2024-01-01');
        $this->userRepository->save($users);

        // the write succeeded despite the observer exception, routed to onError()
        $this->assertEquals(1, $this->executor->getScalar("select count(*) from users where name = 'ErrorSwallowed'"));
        $this->assertCount(1, $observer->errors);
        $this->assertEquals("Observer failure", $observer->errors[0]->getMessage());
    }
}
