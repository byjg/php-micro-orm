<?php

namespace ByJG\MicroOrm\Interface;

use ByJG\AnyDataset\Core\GenericIterator;
use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\AnyDataset\Db\SqlStatement;
use ByJG\MicroOrm\CacheQueryResult;

interface QueryBuilderInterface
{
    public function build(?DbDriverInterface $dbDriver = null): SqlStatement;

    public function buildAndGetIterator(?DbDriverInterface $dbDriver = null, ?CacheQueryResult $cache = null): GenericIterator;
}