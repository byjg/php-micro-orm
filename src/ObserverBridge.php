<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DatabaseEvent;
use ByJG\AnyDataset\Db\DatabaseEventTypeEnum;
use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\AnyDataset\Db\Interfaces\DatabaseEventObserverInterface;
use ByJG\MicroOrm\Enum\ObserverEvent;
use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\MicroOrm\Interface\ObserverProcessorInterface;
use ByJG\MicroOrm\Interface\StatementHookInterface;
use Override;
use Throwable;
use WeakMap;

/**
 * Bridges the low-level anydataset-db executor events to the MicroOrm entity
 * observers (ObserverProcessorInterface).
 *
 * One bridge exists per DatabaseExecutor instance, so observer scope is the
 * database connection: any OrmSqlStatement executed through that executor —
 * by a Repository or directly — notifies the processors watching its table.
 */
final class ObserverBridge implements DatabaseEventObserverInterface
{
    /**
     * @var WeakMap<DatabaseExecutor, ObserverBridge>|null
     */
    private static ?WeakMap $registry = null;

    /**
     * @var array<string, ObserverProcessorInternal[]> keyed by observed table
     */
    private array $processors = [];

    private function __construct()
    {
    }

    /**
     * Get the bridge for the executor, creating and attaching it on demand.
     */
    public static function for(DatabaseExecutor $executor): ObserverBridge
    {
        $bridge = self::find($executor);
        if (is_null($bridge)) {
            if (self::$registry === null) {
                /** @var WeakMap<DatabaseExecutor, ObserverBridge> $registry */
                $registry = new WeakMap();
                self::$registry = $registry;
            }
            $bridge = new ObserverBridge();
            self::$registry[$executor] = $bridge;
            $executor->addObserver($bridge);
        }
        return $bridge;
    }

    /**
     * Get the bridge for the executor, or null if none was created.
     */
    public static function find(DatabaseExecutor $executor): ?ObserverBridge
    {
        if (self::$registry === null || !isset(self::$registry[$executor])) {
            return null;
        }
        $bridge = self::$registry[$executor];
        return $bridge instanceof ObserverBridge ? $bridge : null;
    }

    public function addProcessor(ObserverProcessorInterface $processor, Repository $repository): void
    {
        $repository->getExecutor()->getDriver()->log("Observer: entity " . $repository->getMapper()->getTable() . ", listening for {$processor->getObservedTable()}");
        $table = $processor->getObservedTable();
        foreach ($this->processors[$table] ?? [] as $observer) {
            if (get_class($observer->getObservedProcessor()) === get_class($processor) && get_class($observer->getRepository()) === get_class($repository)) {
                throw new InvalidArgumentException("Observer already exists");
            }
        }
        $this->processors[$table][] = new ObserverProcessorInternal($processor, $repository);
    }

    public function hasProcessor(ObserverProcessorInterface $processor): bool
    {
        foreach ($this->processors as $observers) {
            foreach ($observers as $observer) {
                if ($observer->getObservedProcessor() === $processor) {
                    return true;
                }
            }
        }
        return false;
    }

    public function removeProcessor(ObserverProcessorInterface $processor): void
    {
        foreach ($this->processors as $table => $observers) {
            $this->processors[$table] = array_values(
                array_filter($observers, fn($observer) => $observer->getObservedProcessor() !== $processor)
            );
            if (empty($this->processors[$table])) {
                unset($this->processors[$table]);
            }
        }
    }

    /**
     * @return DatabaseEventTypeEnum[]
     */
    #[Override]
    public function subscribedEvents(): array
    {
        return [DatabaseEventTypeEnum::BEFORE_EXECUTE, DatabaseEventTypeEnum::AFTER_EXECUTE];
    }

    #[Override]
    public function handleEvent(DatabaseEvent $event): void
    {
        $statement = $event->getStatement();
        if (!$statement instanceof OrmSqlStatement || !$statement->hasOrmEvent()) {
            return;
        }
        $table = $statement->getOrmTable();
        if ($table === null || !isset($this->processors[$table])) {
            return;
        }

        if ($event->getType() === DatabaseEventTypeEnum::BEFORE_EXECUTE) {
            foreach ($this->processors[$table] as $observer) {
                $processor = $observer->getObservedProcessor();
                if ($processor instanceof StatementHookInterface) {
                    // exceptions propagate on purpose: throwing here vetoes the write
                    $processor->beforeStatement($statement, $observer->getRepository());
                }
            }
            return;
        }

        // AFTER_EXECUTE
        $context = $statement->getOrmContext();
        $context->markExecuted();
        if ($context->isDeferred()) {
            // Repository::save() flushes after the entity is rehydrated
            $context->addPendingBridge($this);
            return;
        }
        $this->notifyStatement($statement);
    }

    /**
     * Translate the statement into ObserverData and notify the processors
     * watching its table. Exceptions thrown by process() are routed to
     * onError() and never abort the SQL flow.
     */
    public function notifyStatement(OrmSqlStatement $statement): void
    {
        $table = $statement->getOrmTable();
        $event = $statement->getOrmEvent();
        if ($table === null || $event === null || !isset($this->processors[$table])) {
            return;
        }

        $context = $statement->getOrmContext();
        $isDeleteEvent = $event === ObserverEvent::Delete || $event === ObserverEvent::SoftDelete;
        $data = $context->getEntity() ?? ($isDeleteEvent ? null : $statement->getParams());
        $oldData = $context->getOldEntity() ?? ($isDeleteEvent ? $statement->getParams() : null);

        foreach ($this->processors[$table] as $observer) {
            $observer->log("Observer: notifying " . $observer->getMapper()->getTable() . ", changes in $table");

            $observerData = new ObserverData($table, $event, $data, $oldData, $observer->getRepository(), $statement);

            try {
                $observer->getObservedProcessor()->process($observerData);
            } catch (Throwable $e) {
                $observer->getObservedProcessor()->onError($e, $observerData);
            }
        }
    }
}
