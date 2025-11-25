<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\Interfaces\DbDriverInterface;
use ByJG\AnyDataset\Db\Interfaces\SqlDialectInterface;
use ByJG\AnyDataset\Db\SqlStatement;
use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\MicroOrm\Interface\QueryBuilderInterface;
use Override;

class DeleteQuery extends Updatable
{
    public static function getInstance(): DeleteQuery
    {
        return new DeleteQuery();
    }

    #[Override]
    public function build(SqlDialectInterface|DbDriverInterface|null $dbDriverOrHelper = null): SqlStatement
    {
        $whereStr = $this->getWhere();
        if (is_null($whereStr)) {
            throw new InvalidArgumentException('You must specifiy a where clause');
        }

        $sql = 'DELETE FROM ' . $this->table
            . ' WHERE ' . $whereStr[0];

        $params = $whereStr[1];

        $sql = ORMHelper::processLiteral($sql, $params);

        return new SqlStatement($sql, $params);
    }

    #[Override]
    public function convert(?SqlDialectInterface $dbHelper = null): QueryBuilderInterface
    {
        $query = Query::getInstance()
            ->table($this->table);

        foreach ($this->where as $item) {
            $query->where($item['filter'], $item['params']);
        }

        return $query;
    }
}
