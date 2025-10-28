<?php

namespace ByJG\MicroOrm\Interface;

use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\AnyDataset\Db\DbFunctionsInterface;
use ByJG\AnyDataset\Db\SqlStatement;

interface UpdateBuilderInterface
{
    public function build(DbFunctionsInterface|DbDriverInterface|null $dbDriverOrHelper = null): SqlStatement;

    public function buildAndExecute(DbDriverInterface $dbDriver, $params = [], ?DbFunctionsInterface $dbHelper = null);

    public function convert(?DbFunctionsInterface $dbDriver = null): QueryBuilderInterface;
}