<?php

namespace ByJG\MicroOrm\Interface;

use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\MicroOrm\Literal\Literal;

/**
 * Interface for processors that manipulate entity instances before database operations.
 */
interface UniqueIdGeneratorInterface
{
    /**
     * Process an entity instance before it's inserted or updated in the database.
     *
     * @param DatabaseExecutor $executor
     * @param array|object $instance The entity instance to process
     * @return string|int|Literal The processed entity instance
     */
    public function process(DatabaseExecutor $executor, array|object $instance): string|Literal|int;
}
