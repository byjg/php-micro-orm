<?php

namespace ByJG\MicroOrm\Interface;

use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\AnyDataset\Db\Interfaces\DbDriverInterface;
use ByJG\AnyDataset\Db\Interfaces\SqlDialectInterface;
use ByJG\AnyDataset\Db\SqlStatement;

interface UpdateBuilderInterface
{
    public function build(SqlDialectInterface|DbDriverInterface|null $dbDriverOrHelper = null): SqlStatement;

    public function buildAndExecute(DatabaseExecutor $executor, $params = []);

    public function convert(?SqlDialectInterface $dbHelper = null): QueryBuilderInterface;
}