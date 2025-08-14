<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\AnyDataset\Db\DbFunctionsInterface;
use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\MicroOrm\Interface\QueryBuilderInterface;

class DeleteQuery extends Updatable
{
    public static function getInstance(): DeleteQuery
    {
        return new DeleteQuery();
    }

    public function build(DbFunctionsInterface|DbDriverInterface|null $dbDriverOrHelper = null): SqlObject
    {
        $whereStr = $this->getWhere();
        if (is_null($whereStr)) {
            throw new InvalidArgumentException('You must specifiy a where clause');
        }

        $sql = 'DELETE FROM ' . $this->table
            . ' WHERE ' . $whereStr[0];

        $params = $whereStr[1];

        $sql = ORMHelper::processLiteral($sql, $params);

        return new SqlObject($sql, $params, SqlObjectEnum::DELETE);
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
