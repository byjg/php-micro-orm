<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\AnyDataset\Db\DbFunctionsInterface;

abstract class Updatable implements UpdateBuilderInterface
{
    use WhereTrait;

    protected string $table = "";
    protected array $where = [];

    /**
     * Example
     *    $query->table('product');
     *
     * @param string $table
     * @return $this
     */
    public function table(string $table): static
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Example:
     *    $query->filter('price > [[amount]]', [ 'amount' => 1000] );
     *
     * @param string $filter
     * @param array $params
     * @return $this
     */
    public function where($filter, array $params = [])
    {
        $this->where[] = [ 'filter' => $filter, 'params' => $params  ];
        return $this;
    }


    protected function getWhere()
    {
        $whereStr = [];
        $params = [];

        foreach ($this->where as $item) {
            $whereStr[] = $item['filter'];
            $params = array_merge($params, $item['params']);
        }

        if (empty($whereStr)) {
            return null;
        }

        return [ implode(' AND ', $whereStr), $params ];
    }

    public function buildAndExecute(DbDriverInterface $dbDriver, $params = [], ?DbFunctionsInterface $dbHelper = null)
    {
        $sqlObject = $this->build($dbHelper);
        return $dbDriver->execute($sqlObject->getSql(), array_merge($sqlObject->getParameters(), $params));
    }
}
