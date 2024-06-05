<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\AnyDataset\Db\DbFunctionsInterface;
use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\MicroOrm\Exception\OrmInvalidFieldsException;

class UpdateQuery extends Updatable
{
    protected $set = [];

    public static function getInstance()
    {
        return new UpdateQuery();
    }

    /**
     * @param $field
     * @param $value
     * @return $this
     */
    public function set($field, $value)
    {
        $this->set[$field] = $value;
        return $this;
    }

    /**
     * @param array $params
     * @param DbFunctionsInterface|null $dbHelper
     * @return string
     * @throws InvalidArgumentException
     */
    public function build(&$params, DbFunctionsInterface $dbHelper = null)
    {
        if (empty($this->set)) {
            throw new InvalidArgumentException('You must specifiy the fields for update');
        }
        
        $fieldsStr = [];
        foreach ($this->set as $field => $value) {
            $fieldName = $field;
            if (!is_null($dbHelper)) {
                $fieldName = $dbHelper->delimiterField($fieldName);
            }
            $fieldsStr[] = "$fieldName = [[$field]] ";
            $params[$field] = $value;
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
    public function convert(?DbFunctionsInterface $dbDriver = null): QueryBuilderInterface
    {
        $query = Query::getInstance()
            ->fields(array_keys($this->set))
            ->table($this->table);

        foreach ($this->where as $item) {
            $query->where($item['filter'], $item['params']);
        }

        return $query;
    }
}
