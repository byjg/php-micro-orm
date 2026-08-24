<?php

namespace ByJG\MicroOrm;

/**
 * Mutable runtime context attached to an OrmSqlStatement.
 *
 * The DatabaseExecutor clones statements (shallow) before notifying observers,
 * so this object is intentionally shared by reference between the original
 * statement held by the Repository and the clone the observers receive.
 */
class OrmEventContext
{
    protected mixed $entity = null;

    protected mixed $oldEntity = null;

    protected bool $deferred = false;

    protected bool $executed = false;

    /**
     * @var ObserverBridge[]
     */
    protected array $pendingBridges = [];

    public function setEntities(mixed $entity, mixed $oldEntity): void
    {
        $this->entity = $entity;
        $this->oldEntity = $oldEntity;
    }

    public function getEntity(): mixed
    {
        return $this->entity;
    }

    public function getOldEntity(): mixed
    {
        return $this->oldEntity;
    }

    /**
     * Postpone observer notification: at AFTER_EXECUTE the bridge parks itself
     * here instead of dispatching, and the caller flushes via drainPendingBridges()
     * once the entity is fully hydrated (e.g. generated keys copied back).
     */
    public function defer(): void
    {
        $this->deferred = true;
    }

    public function isDeferred(): bool
    {
        return $this->deferred;
    }

    public function markExecuted(): void
    {
        $this->executed = true;
    }

    public function isExecuted(): bool
    {
        return $this->executed;
    }

    public function addPendingBridge(ObserverBridge $bridge): void
    {
        if (!in_array($bridge, $this->pendingBridges, true)) {
            $this->pendingBridges[] = $bridge;
        }
    }

    /**
     * @return ObserverBridge[]
     */
    public function drainPendingBridges(): array
    {
        $bridges = $this->pendingBridges;
        $this->pendingBridges = [];
        return $bridges;
    }
}
