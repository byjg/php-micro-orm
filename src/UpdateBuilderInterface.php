<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\AnyDataset\Db\DbFunctionsInterface;

interface UpdateBuilderInterface
{
    public function build(?DbFunctionsInterface $dbHelper = null): SqlObject;

    public function buildAndExecute(DbDriverInterface $dbDriver, $params = [], ?DbFunctionsInterface $dbHelper = null);

    public function convert(?DbFunctionsInterface $dbDriver = null): QueryBuilderInterface;
}