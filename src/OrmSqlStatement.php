<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\SqlStatement;
use ByJG\MicroOrm\Enum\ObserverEvent;

/**
 * A SqlStatement carrying the ORM intent (event type + affected table) so the
 * ObserverBridge can translate low-level executor events into entity observer
 * notifications, no matter where the statement is executed.
 */
class OrmSqlStatement extends SqlStatement
{
    protected ?ObserverEvent $ormEvent;

    protected ?string $ormTable;

    protected ?OrmEventContext $ormContext = null;

    public function __construct(string $sql, ?array $params = [], ?ObserverEvent $ormEvent = null, ?string $ormTable = null)
    {
        parent::__construct($sql, $params);
        $this->ormEvent = $ormEvent;
        $this->ormTable = $ormTable;
    }

    public function withOrmEvent(ObserverEvent $ormEvent, string $ormTable): static
    {
        $statement = clone $this;
        $statement->ormEvent = $ormEvent;
        $statement->ormTable = $ormTable;
        return $statement;
    }

    public function getOrmEvent(): ?ObserverEvent
    {
        return $this->ormEvent;
    }

    public function getOrmTable(): ?string
    {
        return $this->ormTable;
    }

    public function hasOrmEvent(): bool
    {
        return $this->ormEvent !== null && $this->ormTable !== null;
    }

    /**
     * Lazily created. Clones made by the executor share the same context by
     * reference, but only if it exists before the clone — callers that need to
     * read the context after execution must call this before handing the
     * statement to the executor.
     */
    public function getOrmContext(): OrmEventContext
    {
        return $this->ormContext ??= new OrmEventContext();
    }
}
