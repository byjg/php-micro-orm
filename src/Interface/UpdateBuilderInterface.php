<?php

namespace ByJG\MicroOrm\Interface;

use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\AnyDataset\Db\DbFunctionsInterface;
use ByJG\AnyDataset\Db\SqlStatement;

interface UpdateBuilderInterface
{
    public function build(DbFunctionsInterface|DbDriverInterface|null $dbDriverOrHelper = null): SqlStatement;

    public function buildAndExecute(DatabaseExecutor $executor, $params = []);

    public function convert(?DbFunctionsInterface $dbHelper = null): QueryBuilderInterface;
}