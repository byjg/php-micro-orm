<?php

namespace ByJG\MicroOrm\Interface;

use ByJG\AnyDataset\Core\GenericIterator;
use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\MicroOrm\SqlObject;

interface QueryBuilderInterface
{
    public function build(?DbDriverInterface $dbDriver = null): SqlObject;

    public function buildAndGetIterator(?DbDriverInterface $dbDriver = null): GenericIterator;
}