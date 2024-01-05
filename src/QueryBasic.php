<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\Serializer\SerializerObject;

class QueryBasic implements QueryBuilderInterface
{
    protected $fields = [];
    protected $table = "";
    protected $alias = "";
    protected $where = [];
    protected $join = [];
    protected $dbDriver = null;
    protected $recursive = null;

    public static function getInstance(): QueryBasic
    {
        return new QueryBasic();
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
            $this->field($field);
        }

        return $this;
    }

    public function field($field, $alias = null)
    {
        if ($field instanceof Mapper) {
            $this->addFieldFromMapper($field);
            return $this;
        }

        if ($field instanceof QueryBasic && empty($alias)) {
            throw new InvalidArgumentException("You must define an alias for the sub query");
        }

        if (!empty($alias)) {
            $this->fields[$alias] = $field;
        } else {
            $this->fields[] = $field;
        }

        return $this;
    }

    /**
     * @param \ByJG\MicroOrm\Mapper $mapper
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     */
    protected function addFieldFromMapper(Mapper $mapper)
    {
        $entityClass = $mapper->getEntity();
        $entity = new $entityClass();
        $serialized = SerializerObject::instance($entity)->serialize();

        foreach (array_keys($serialized) as $fieldName) {
            $fieldMapping = $mapper->getFieldMap($fieldName);
            if (empty($fieldMapping)) {
                $mapField = $fieldName;
                $alias = null;
            } else {
                if (!$fieldMapping->isSyncWithDb()) {
                    continue;
                }
                $mapField = $fieldMapping->getFieldName();
                $alias = $fieldMapping->getFieldAlias();
            }

            $this->field($mapper->getTable() . '.' . $mapField, $alias);
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

    public function crossJoin($table, $alias = null)
    {
        $this->join[] = [ 'table'=>$table, 'filter'=>'', 'type' => 'CROSS', 'alias' => empty($alias) ? $table : $alias];
        return $this;
    }

    public function withRecursive(Recursive $recursive)
    {
        $this->recursive = $recursive;
        if (empty($this->table)) {
            $this->table($recursive->getTableName());
        }
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


    protected function getFields()
    {
        if (empty($this->fields)) {
            return [' * ', [] ];
        }

        $fieldList = '';
        $params = [];
        foreach ($this->fields as $alias => $field) {
            if (!empty($fieldList)) {
                $fieldList .= ', ';
            }
            if (is_numeric($alias)) {
                $fieldList .= $field;
            } elseif ($field instanceof QueryBasic) {
                $subQuery = $field->build($this->dbDriver);
                $fieldList .= '(' . $subQuery['sql'] . ') as ' . $alias;
                $params = array_merge($params, $subQuery['params']);
            } else {
                $fieldList .= $field . ' as ' . $alias;
            }
        }

        return [' ' . $fieldList . ' ', $params ];
    }

    /**
     * @return array
     * @throws InvalidArgumentException
     */
    protected function getJoin()
    {
        [ $joinStr, $params ] = $this->buildTable($this->table, $this->alias);
        foreach ($this->join as $item) {
            [ $table, $moreParams ] = $this->buildTable($item['table'], $item['alias'], false);
            $joinStr .= ' ' . $item['type'] . " JOIN $table";
            if (!empty($item['filter'])) {
                $joinStr .= " ON " . $item['filter'];
            }
            $params = array_merge($params, $moreParams);
        }
        return [ $joinStr, $params ];
    }

    protected function buildTable($table, $alias, $supportParams = true)
    {
        $params = [];
        if ($table instanceof QueryBasic) {
            $subQuery = $table->build($this->dbDriver);
            if (!empty($subQuery["params"]) && !$supportParams) {
                throw new InvalidArgumentException("SubQuery does not support filters");
            }
            if (empty($alias) || $alias instanceof QueryBasic) {
                throw new InvalidArgumentException("SubQuery requires you define an alias");
            }
            $table = "({$subQuery["sql"]})";
            $params = $subQuery["params"];
        }
        return [ $table . (!empty($alias) && $table != $alias ? " as " . $alias : ""), $params ];
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
    public function build(?DbDriverInterface $dbDriver = null)
    {
        $this->dbDriver = $dbDriver;

        $sql = "";
        if (!empty($this->recursive)) {
            $sql = $this->recursive->build($dbDriver);
        }

        [ $fieldList , $params ] = $this->getFields();
        [ $tableList , $paramsTable ] = $this->getJoin();

        $params = array_merge($params, $paramsTable);

        $sql .= "SELECT " .
            $fieldList .
            "FROM " . $tableList;
        
        $whereStr = $this->getWhere();
        if (!is_null($whereStr)) {
            $sql .= ' WHERE ' . $whereStr[0];
            $params = array_merge($params, $whereStr[1]);
        }

        $sql = ORMHelper::processLiteral($sql, $params);

        return [ 'sql' => $sql, 'params' => $params ];
    }
}
