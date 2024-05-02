<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\AnyDataset\Db\DbFunctionsInterface;
use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\MicroOrm\Exception\OrmInvalidFieldsException;

class InsertQuery extends Updatable
{
    protected $fields = [];

    public static function getInstance()
    {
        return new InsertQuery();
    }

    /**
     * Fields to be used for the INSERT
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

    protected function getFields()
    {
        if (empty($this->fields)) {
            return ' * ';
        }

        return ' ' . implode(', ', $this->fields) . ' ';
    }
    
    /**
     * @param $params
     * @param DbFunctionsInterface|null $dbHelper
     * @return null|string|string[]
     * @throws \ByJG\MicroOrm\Exception\OrmInvalidFieldsException
     */
    public function build(&$params, DbFunctionsInterface $dbHelper = null)
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

    public function convert(?DbFunctionsInterface $dbDriver = null): QueryBuilderInterface
    {
        $query = Query::getInstance()
            ->fields($this->fields)
            ->table($this->table);

        foreach ($this->where as $item) {
            $query->where($item['filter'], $item['params']);
        }

        return $query;
    }
}
