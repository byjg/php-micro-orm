<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\Serializer\BinderObject;

class Query
{
    protected $fields = [];
    protected $table = "";
    protected $alias = "";
    protected $where = [];
    protected $groupBy = [];
    protected $orderBy = [];
    protected $join = [];
    protected $limitStart = null;
    protected $limitEnd = null;
    protected $top = null;
    protected $dbDriver = null;

    protected $forUpdate = false;

    public static function getInstance()
    {
        return new Query();
    }

    /**
     * Example:
     *   $query->fields(['name', 'price']);
     *
     * @param array $fields
     * @return $this
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     */
    public function fields(array $fields)
    {
        foreach ($fields as $field) {
            if ($field instanceof Mapper) {
                $this->addFieldFromMapper($field);
                continue;
            }
            $this->fields[] = $field;
        }

        return $this;
    }

    /**
     * @param \ByJG\MicroOrm\Mapper $mapper
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     */
    private function addFieldFromMapper(Mapper $mapper)
    {
        $entityClass = $mapper->getEntity();
        $entity = new $entityClass();
        $serialized = BinderObject::toArrayFrom($entity);

        foreach (array_keys($serialized) as $fieldName) {
            $mapField = $mapper->getFieldMap($fieldName, Mapper::FIELDMAP_FIELD);
            if (empty($mapField)) {
                $mapField = $fieldName;
            }

            $alias = $mapper->getFieldAlias($mapField);
            if (!empty($alias)) {
                $alias = ' as ' . $alias;
            }

            $this->fields[] = $mapper->getTable() . '.' . $mapField . $alias;
        }
    }

    /**
     * Example
     *    $query->table('product');
     *
     * @param string $table
     * @param string $alias
     * @return $this
     */
    public function table($table, $alias = null)
    {
        $this->table = $table;
        $this->alias = $alias;

        return $this;
    }

    /**
     * Example:
     *    $query->join('sales', 'product.id = sales.id');
     *
     * @param Query|string $table
     * @param string $filter
     * @param string $alias
     * @return $this
     */
    public function join($table, $filter, $alias = null)
    {
        $this->join[] = [ 'table'=>$table, 'filter'=>$filter, 'type' => 'INNER', 'alias' => empty($alias) ? $table : $alias];
        return $this;
    }

    /**
     * Example:
     *    $query->leftJoin('sales', 'product.id = sales.id');
     *
     * @param Query|string $table
     * @param string $filter
     * @param string $alias
     * @return $this
     */
    public function leftJoin($table, $filter, $alias = null)
    {
        $this->join[] = [ 'table'=>$table, 'filter'=>$filter, 'type' => 'LEFT', 'alias' => empty($alias) ? $table : $alias];
        return $this;
    }

    /**
     * Example:
     *    $query->rightJoin('sales', 'product.id = sales.id');
     *
     * @param Query|string $table
     * @param string $filter
     * @param string $alias
     * @return $this
     */
    public function rightJoin($table, $filter, $alias = null)
    {
        $this->join[] = [ 'table'=>$table, 'filter'=>$filter, 'type' => 'RIGHT', 'alias' => empty($alias) ? $table : $alias];
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

    /**
     * Example:
     *    $query->groupBy(['name']);
     *
     * @param array $fields
     * @return $this
     */
    public function groupBy(array $fields)
    {
        $this->groupBy = array_merge($this->groupBy, $fields);
    
        return $this;
    }

    /**
     * Example:
     *     $query->orderBy(['price desc']);
     *
     * @param array $fields
     * @return $this
     */
    public function orderBy(array $fields)
    {
        $this->orderBy = array_merge($this->orderBy, $fields);

        return $this;
    }

    public function forUpdate()
    {
        $this->forUpdate = true;
        
        return $this;
    }

    /**
     * @param $start
     * @param $end
     * @return $this
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     */
    public function limit($start, $end)
    {
        if (!is_null($this->top)) {
            throw new InvalidArgumentException('You cannot mix TOP and LIMIT');
        }
        $this->limitStart = $start;
        $this->limitEnd = $end;
        return $this;
    }

    /**
     * @param $top
     * @return $this
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     */
    public function top($top)
    {
        if (!is_null($this->limitStart)) {
            throw new InvalidArgumentException('You cannot mix TOP and LIMIT');
        }
        $this->top = $top;
        return $this;
    }

    protected function getFields()
    {
        if (empty($this->fields)) {
            return ' * ';
        }

        return ' ' . implode(', ', $this->fields) . ' ';
    }

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    protected function getJoin()
    {
        $joinStr = $this->table . (!empty($this->alias) ? " as " . $this->alias : "");
        foreach ($this->join as $item) {
            $table = $item['table'];
            if ($table instanceof Query) {
                $subQuery = $table->build($this->dbDriver);
                if (!empty($subQuery["params"])) {
                    throw new InvalidArgumentException("SubQuery does not support filters");
                }
                if ($item["alias"] instanceof Query) {
                    throw new InvalidArgumentException("SubQuery requires you define an alias");
                }
                $table = "(${subQuery["sql"]})";
            }
            $alias = $item['table'] == $item['alias'] ? "" : " as ". $item['alias'];
            $joinStr .= ' ' . $item['type'] . " JOIN $table$alias ON " . $item['filter'];
        }
        return $joinStr;
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

    /**
     * @param \ByJG\AnyDataset\Db\DbDriverInterface|null $dbDriver
     * @return array
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     */
    public function build(DbDriverInterface $dbDriver = null)
    {
        $this->dbDriver = $dbDriver;

        $sql = "SELECT " .
            $this->getFields() .
            "FROM " . $this->getJoin();
        
        $whereStr = $this->getWhere();
        $params = null;
        if (!is_null($whereStr)) {
            $sql .= ' WHERE ' . $whereStr[0];
            $params = $whereStr[1];
        }

        $sql .= $this->addGroupBy();

        $sql .= $this->addOrderBy();

        $sql = $this->addforUpdate($dbDriver, $sql);

        $sql = $this->addTop($dbDriver, $sql);

        $sql = $this->addLimit($dbDriver, $sql);

        $sql = ORMHelper::processLiteral($sql, $params);

        return [ 'sql' => $sql, 'params' => $params ];
    }

    private function addOrderBy()
    {
        if (empty($this->orderBy)) {
            return "";
        }
        return ' ORDER BY ' . implode(', ', $this->orderBy);
    }

    private function addGroupBy()
    {
        if (empty($this->groupBy)) {
            return "";
        }
        return ' GROUP BY ' . implode(', ', $this->groupBy);
    }

    /**
     * @param DbDriverInterface $dbDriver
     * @param string $sql
     * @return string
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     */
    private function addforUpdate($dbDriver, $sql)
    {
        if (empty($this->forUpdate)) {
            return $sql;
        }

        if (is_null($dbDriver)) {
            throw new InvalidArgumentException('To get FOR UPDATE working you have to pass the DbDriver');
        }

        return $dbDriver->getDbHelper()->forUpdate($sql);
    }

    /**
     * @param DbDriverInterface $dbDriver
     * @param string $sql
     * @return string
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     */
    private function addTop($dbDriver, $sql)
    {
        if (empty($this->top)) {
            return $sql;
        }

        if (is_null($dbDriver)) {
            throw new InvalidArgumentException('To get Limit and Top working you have to pass the DbDriver');
        }

        return $dbDriver->getDbHelper()->top($sql, $this->top);
    }

    /**
     * @param DbDriverInterface $dbDriver
     * @param string $sql
     * @return string
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     */
    private function addLimit($dbDriver, $sql)
    {
        if (empty($this->limitStart) && ($this->limitStart !== 0)) {
            return $sql;
        }

        if (is_null($dbDriver)) {
            throw new InvalidArgumentException('To get Limit and Top working you have to pass the DbDriver');
        }

        return $dbDriver->getDbHelper()->limit($sql, $this->limitStart, $this->limitEnd);
    }
}
