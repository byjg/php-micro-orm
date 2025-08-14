<?php

namespace ByJG\MicroOrm\Interface;

use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\AnyDataset\Db\DbFunctionsInterface;
use ByJG\MicroOrm\SqlObject;

interface UpdateBuilderInterface
{
    public function build(DbFunctionsInterface|DbDriverInterface|null $dbDriverOrHelper = null): SqlObject;

    public function buildAndExecute(DbDriverInterface $dbDriver, $params = [], ?DbFunctionsInterface $dbHelper = null);

    public function convert(?DbFunctionsInterface $dbDriver = null): QueryBuilderInterface;
}