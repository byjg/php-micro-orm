<?php

namespace ByJG\MicroOrm\Interface;

use ByJG\MicroOrm\OrmSqlStatement;
use ByJG\MicroOrm\Repository;

/**
 * Optional add-on for ObserverProcessorInterface implementations.
 *
 * When the processor also implements this interface, it is called right before
 * the SQL statement of an observed table executes, with full access to the SQL
 * and its parameters. Throwing an exception here aborts the write (veto).
 */
interface StatementHookInterface
{
    public function beforeStatement(OrmSqlStatement $statement, Repository $repository): void;
}
