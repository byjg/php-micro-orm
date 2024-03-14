<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\AnyDataset\Db\DbFunctionsInterface;

interface UpdateBuilderInterface
{
    public function buildInsert(&$params, DbFunctionsInterface $dbHelper = null);

    public function buildUpdate(&$params, DbFunctionsInterface $dbHelper = null);

    public function buildDelete(&$params);
}