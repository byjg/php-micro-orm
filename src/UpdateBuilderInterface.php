<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\AnyDataset\Db\DbFunctionsInterface;

interface UpdateBuilderInterface
{
    public function buildInsert(array &$params, DbFunctionsInterface $dbHelper = null);

    public function buildUpdate(array &$params, DbFunctionsInterface $dbHelper = null);

    public function buildDelete(array &$params);
}