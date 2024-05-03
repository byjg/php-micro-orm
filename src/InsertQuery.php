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
    public function field($field, $value)
    {
        $this->fields[$field] = $value;
        return $this;
    }

    protected function getFields()
    {
        return ' ' . implode(', ', $this->fields) . ' ';
    }
    
    /**
     * @param $params
     * @param DbFunctionsInterface|null $dbHelper
     * @return null|string|string[]
     * @throws \ByJG\MicroOrm\Exception\OrmInvalidFieldsException
     */
    public function build(DbFunctionsInterface $dbHelper = null): SqlObject
    {
        if (empty($this->fields)) {
            throw new OrmInvalidFieldsException('You must specifiy the fields for insert');
        }

        $fieldsStr = array_keys($this->fields);
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
            . '( [[' . implode(']], [[', array_keys($this->fields)) . ']] ) ';

        $params = $this->fields;
        $sql = ORMHelper::processLiteral($sql, $params);
        return new SqlObject($sql, $params, SqlObjectEnum::INSERT);
    }

    public function convert(?DbFunctionsInterface $dbDriver = null): QueryBuilderInterface
    {
        $query = Query::getInstance()
            ->fields(array_keys($this->fields))
            ->table($this->table);

        foreach ($this->where as $item) {
            $query->where($item['filter'], $item['params']);
        }

        return $query;
    }
}
