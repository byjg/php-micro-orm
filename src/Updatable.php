<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DbFunctionsInterface;
use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\MicroOrm\Exception\OrmInvalidFieldsException;

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
     * @param $params
     * @param DbFunctionsInterface|null $dbHelper
     * @return null|string|string[]
     * @throws \ByJG\MicroOrm\Exception\OrmInvalidFieldsException
     */
    public function buildInsert(&$params, DbFunctionsInterface $dbHelper = null)
    {
        if (empty($this->fields)) {
            throw new OrmInvalidFieldsException('You must specifiy the fields for insert');
        }

        $fieldsStr = $this->fields;
        if (!is_null($dbHelper)) {
            $fieldsStr = $dbHelper->delimiterField($fieldsStr);
        }

        $tableStr = $this->table;
        if (!is_null($dbHelper)) {
            $tableStr = $dbHelper->delimiterTable($tableStr);
        }

        $sql = 'INSERT INTO '
            . $tableStr
            . '( ' . implode(', ', $fieldsStr) . ' ) '
            . ' values '
            . '( [[' . implode(']], [[', $this->fields) . ']] ) ';

        return ORMHelper::processLiteral($sql, $params);
    }

    /**
     * @param array $params
     * @param DbFunctionsInterface|null $dbHelper
     * @return string
     * @throws InvalidArgumentException
     */
    public function buildUpdate(&$params, DbFunctionsInterface $dbHelper = null)
    {
        if (empty($this->fields)) {
            throw new InvalidArgumentException('You must specifiy the fields for insert');
        }
        
        $fieldsStr = [];
        foreach ($this->fields as $field) {
            $fieldName = $field;
            if (!is_null($dbHelper)) {
                $fieldName = $dbHelper->delimiterField($fieldName);
            }
            $fieldsStr[] = "$fieldName = [[$field]] ";
        }
        
        $whereStr = $this->getWhere();
        if (is_null($whereStr)) {
            throw new InvalidArgumentException('You must specifiy a where clause');
        }

        $tableName = $this->table;
        if (!is_null($dbHelper)) {
            $tableName = $dbHelper->delimiterTable($tableName);
        }

        $sql = 'UPDATE ' . $tableName . ' SET '
            . implode(', ', $fieldsStr)
            . ' WHERE ' . $whereStr[0];

        $params = array_merge($params, $whereStr[1]);

        return ORMHelper::processLiteral($sql, $params);
    }

    /**
     * @param array $params
     * @return string
     * @throws InvalidArgumentException
     */
    public function buildDelete(&$params)
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
}
