<?php

namespace ByJG\MicroOrm\Interface;

use ByJG\AnyDataset\Core\GenericIterator;
use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\AnyDataset\Db\SqlStatement;
use ByJG\MicroOrm\CacheQueryResult;

interface QueryBuilderInterface
{
    /**
     * Build the SQL statement for this query.
     *
     * This method is part of the **Infrastructure Layer** - it generates SQL without any
     * knowledge of domain entities or mapping logic.
     *
     * @param DbDriverInterface|null $dbDriver Optional driver for database-specific SQL formatting
     * @return SqlStatement The built SQL statement with parameters
     */
    public function build(?DbDriverInterface $dbDriver = null): SqlStatement;

    /**
     * Build and execute the query, returning a raw data iterator without entity transformation
     *
     * @param DatabaseExecutor $executor The database executor to use for query execution
     * @param CacheQueryResult|null $cache Optional cache configuration
     * @return GenericIterator Iterator over raw database rows (no entity transformation)
     *
     * @see Repository::getIterator() For domain-layer entity-aware data access
     * @see Repository::getByQuery() For multi-mapper queries with entity transformation
     */
    public function buildAndGetIterator(DatabaseExecutor $executor, ?CacheQueryResult $cache = null): GenericIterator;
}