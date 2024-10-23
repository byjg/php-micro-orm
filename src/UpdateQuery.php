<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DbFunctionsInterface;
use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\MicroOrm\Interface\QueryBuilderInterface;
use ByJG\MicroOrm\Literal\LiteralInterface;

class UpdateQuery extends Updatable
{
    protected array $set = [];

    /**
     * @throws InvalidArgumentException
     */
    public static function getInstance(array $fields = [], Mapper $mapper = null): UpdateQuery
    {
        $updatable = new UpdateQuery();

        if (!is_null($mapper)) {
            $updatable->table($mapper->getTable());

            $pkFields = array_map(function ($item) use (&$fields) {
                $value = $fields[$item];
                unset($fields[$item]);
                return $value;
            }, $mapper->getPrimaryKey());

            [$filterList, $filterKeys] = $mapper->getPkFilter($pkFields);
            $updatable->where($filterList, $filterKeys);
        }

        foreach ($fields as $field => $value) {
            $updatable->set($field, $value);
        }

        return $updatable;
    }

    /**
     * @param string $field
     * @param int|float|bool|string|LiteralInterface|null $value
     * @return $this
     */
    public function set(string $field, int|float|bool|string|LiteralInterface|null $value): UpdateQuery
    {
        $this->set[$field] = $value;
        return $this;
    }

    /**
     * @param DbFunctionsInterface|null $dbHelper
     * @return SqlObject
     * @throws InvalidArgumentException
     */
    public function build(DbFunctionsInterface $dbHelper = null): SqlObject
    {
        if (empty($this->set)) {
            throw new InvalidArgumentException('You must specify the fields for update');
        }
        
        $fieldsStr = [];
        $params = [];
        foreach ($this->set as $field => $value) {
            $fieldName = $field;
            if (!is_null($dbHelper)) {
                $fieldName = $dbHelper->delimiterField($fieldName);
            }
            $fieldsStr[] = "$fieldName = :$field ";
            $params[$field] = $value;
        }
        
        $whereStr = $this->getWhere();
        if (is_null($whereStr)) {
            throw new InvalidArgumentException('You must specify a where clause');
        }

        $tableName = $this->table;
        if (!is_null($dbHelper)) {
            $tableName = $dbHelper->delimiterTable($tableName);
        }

        $sql = 'UPDATE ' . $tableName . ' SET '
            . implode(', ', $fieldsStr)
            . ' WHERE ' . $whereStr[0];

        $params = array_merge($params, $whereStr[1]);

        $sql = ORMHelper::processLiteral($sql, $params);
        return new SqlObject($sql, $params, SqlObjectEnum::UPDATE);
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
