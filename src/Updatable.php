<?php
/**
 * Created by PhpStorm.
 * User: jg
 * Date: 21/06/16
 * Time: 12:01
 */

namespace ByJG\MicroOrm;


use ByJG\AnyDataset\DbFunctionsInterface;

class Updatable
{
    protected $fields = [];
    protected $table = "";
    protected $where = [];

    public static function getInstance()
    {
        return new Updatable();
    }

    /**
     * Example:
     *   $query->fields(['name', 'price']);
     * 
     * @param array $fields
     * @return $this
     */
    public function fields(array $fields)
    {
        $this->fields = array_merge($this->fields, (array)$fields);
        
        return $this;
    }

    /**
     * Example
     *    $query->table('product');
     * 
     * @param string $table
     * @return $this
     */
    public function table($table) 
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

    protected function getFields()
    {
        if (empty($this->fields)) {
            return ' * ';
        }

        return ' ' . implode(', ', $this->fields) . ' ';
    }
    
    protected function getWhere()
    {
        $where = [];
        $params = [];

        foreach ($this->where as $item) {
            $where[] = $item['filter'];
            $params = array_merge($params, $item['params']);
        }
        
        if (empty($where)) {
            return null;
        }
        
        return [ implode(' AND ', $where), $params ];
    }

    /**
     * @param \ByJG\AnyDataset\DbFunctionsInterface|null $dbHelper
     * @param $params
     * @return string
     * @throws \Exception
     */
    public function buildInsert(&$params, DbFunctionsInterface $dbHelper = null)
    {
        if (empty($this->fields)) {
            throw new \Exception('You must specifiy the fields for insert');
        }

        $fields = $this->fields;
        if (!is_null($dbHelper)) {
            $fields = $dbHelper->delimiterField($fields);
        }

        $table = $this->table;
        if (!is_null($dbHelper)) {
            $table = $dbHelper->delimiterTable($table);
        }

        $sql = 'INSERT INTO '
            . $table
            . '( ' . implode(', ', $fields) . ' ) '
            . ' values '
            . '( [[' . implode(']], [[', $this->fields) . ']] ) ';

        $sql = ORMHelper::processLiteral($sql, $params);

        return $sql;
    }

    /**
     * @param \ByJG\AnyDataset\DbFunctionsInterface|null $dbHelper
     * @param $params
     * @return array
     * @throws \Exception
     */
    public function buildUpdate(&$params, DbFunctionsInterface $dbHelper = null)
    {
        if (empty($this->fields)) {
            throw new \InvalidArgumentException('You must specifiy the fields for insert');
        }
        
        $fields = [];
        foreach ($this->fields as $field) {
            $fieldName = $field;
            if (!is_null($dbHelper)) {
                $fieldName = $dbHelper->delimiterField($fieldName);
            }
            $fields[] = "$fieldName = [[$field]] ";
        }
        
        $where = $this->getWhere();
        if (is_null($where)) {
            throw new \InvalidArgumentException('You must specifiy a where clause');
        }

        $tableName = $this->table;
        if (!is_null($dbHelper)) {
            $tableName = $dbHelper->delimiterTable($tableName);
        }

        $sql = 'UPDATE ' . $tableName . ' SET '
            . implode(', ', $fields)
            . ' WHERE ' . $where[0];

        $params = array_merge($params, $where[1]);

        $sql = ORMHelper::processLiteral($sql, $params);

        return $sql;
    }

    /**
     * @param $params
     * @return array
     * @throws \Exception
     */
    public function buildDelete(&$params)
    {
        $where = $this->getWhere();
        if (is_null($where)) {
            throw new \InvalidArgumentException('You must specifiy a where clause');
        }

        $sql = 'DELETE FROM ' . $this->table
            . ' WHERE ' . $where[0];

        $params = array_merge($params, $where[1]);

        $sql = ORMHelper::processLiteral($sql, $params);

        return $sql;
    }
}
