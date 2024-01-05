<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DbDriverInterface;

interface QueryBuilderInterface
{
    public function build(?DbDriverInterface $dbDriver = null);
}