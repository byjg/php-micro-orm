<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\AnyDataset\Db\DbFunctionsInterface;
use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\MicroOrm\Exception\OrmInvalidFieldsException;

class DeleteQuery extends Updatable
{
    public static function getInstance()
    {
        return new DeleteQuery();
    }

    public function build(&$params, DbFunctionsInterface $dbHelper = null)
    {
        $whereStr = $this->getWhere();
        if (is_null($whereStr)) {
            throw new InvalidArgumentException('You must specifiy a where clause');
        }

        $sql = 'DELETE FROM ' . $this->table
            . ' WHERE ' . $whereStr[0];

        $params = array_merge($params, $whereStr[1]);

        return ORMHelper::processLiteral($sql, $params);
    }

    public function convert(?DbFunctionsInterface $dbDriver = null): QueryBuilderInterface
    {
        $query = Query::getInstance()
            ->table($this->table);

        foreach ($this->where as $item) {
            $query->where($item['filter'], $item['params']);
        }

        return $query;
    }
}
