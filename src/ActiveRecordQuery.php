<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Core\Exception\NotFoundException;
use ByJG\AnyDataset\Core\GenericIterator;

/**
 * Extended Query class for Active Record with convenient iterator methods.
 * Allows fluent method chaining directly from Active Record models.
 */
class ActiveRecordQuery extends Query
{
    private Repository $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;

        // Initialize with the table from the repository's mapper
        $mapper = $repository->getMapper();
        $this->table($mapper->getTable(), $mapper->getTableAlias());
    }

    /**
     * Get the iterator for this query with entity transformation
     *
     * @param CacheQueryResult|null $cache
     * @return GenericIterator
     */
    public function getIterator(?CacheQueryResult $cache = null): GenericIterator
    {
        return $this->repository->getIterator($this, $cache);
    }

    /**
     * Get the first entity matching the query, or null if not found
     *
     * @return mixed|null
     */
    public function first(): mixed
    {
        return $this->getIterator()->first();
    }

    /**
     * Get the first entity matching the query, or throw an exception if not found
     *
     * @return mixed
     * @throws NotFoundException
     */
    public function firstOrFail(): mixed
    {
        return $this->getIterator()->firstOrFail();
    }

    /**
     * Check if any entities exist matching the query
     *
     * @return bool
     */
    public function exists(): bool
    {
        return $this->getIterator()->exists();
    }

    /**
     * Check if any entities exist matching the query, throw exception if not
     *
     * @return bool Always returns true (throws exception if no records exist)
     * @throws NotFoundException
     */
    public function existsOrFail(): bool
    {
        return $this->getIterator()->existsOrFail();
    }

    /**
     * Get all entities matching the query as an array
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->repository->getByQuery($this);
    }

    /**
     * Alias for where() to enable fluent syntax from Active Record
     * This allows: Model::where('field = :value', ['value' => 123])->first()
     *
     * @param string $filter
     * @param array $params
     * @return static
     */
    public static function createWhere(Repository $repository, string $filter, array $params = []): static
    {
        $query = new static($repository);
        return $query->where($filter, $params);
    }
}
