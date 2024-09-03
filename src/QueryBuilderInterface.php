<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Core\GenericIterator;
use ByJG\AnyDataset\Db\DbDriverInterface;

interface QueryBuilderInterface
{
    public function build(?DbDriverInterface $dbDriver = null);

    public function buildAndGetIterator(?DbDriverInterface $dbDriver = null): GenericIterator;
}