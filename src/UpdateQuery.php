<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DbFunctionsInterface;
use ByJG\AnyDataset\Db\SqlStatement;
use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\MicroOrm\Interface\QueryBuilderInterface;
use ByJG\MicroOrm\Literal\LiteralInterface;
use Override;

class UpdateQuery extends Updatable
{
    protected array $set = [];

    protected array $joinTables = [];

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

    protected function getJoinTables(DbFunctionsInterface $dbHelper = null): array
    {
        if (is_null($dbHelper)) {
            if (!empty($this->joinTables)) {
                throw new InvalidArgumentException('You must specify a DbFunctionsInterface to use join tables');
            }
            return ['sql' => '', 'position' => 'before_set'];
        }

        return $dbHelper->getJoinTablesUpdate($this->joinTables);
    }

    public function join(string $table, string $joinCondition): UpdateQuery
    {
        $this->joinTables[] = ["table" => $table, "condition" => $joinCondition];
        return $this;
    }

    /**
     * @param DbFunctionsInterface|null $dbHelper
     * @return SqlStatement
     * @throws InvalidArgumentException
     */
    #[Override]
    public function build(DbFunctionsInterface $dbHelper = null): SqlStatement
    {
        if (empty($this->set)) {
            throw new InvalidArgumentException('You must specify the fields for update');
        }
        
        $fieldsStr = [];
        $params = [];
        foreach ($this->set as $field => $value) {
            $fieldName = explode('.', $field);
            $paramName = preg_replace('/[^A-Za-z0-9_]/', '', $fieldName[count($fieldName) - 1]);
            if (!is_null($dbHelper)) {
                foreach ($fieldName as $key => $item) {
                    $fieldName[$key] = $dbHelper->delimiterField($item);
                }
            }
            /** @psalm-suppress InvalidArgument $fieldName */
            $fieldName = implode('.', $fieldName);
            $fieldsStr[] = "$fieldName = :{$paramName} ";
            $params[$paramName] = $value;
        }
        
        $whereStr = $this->getWhere();
        if (is_null($whereStr)) {
            throw new InvalidArgumentException('You must specify a where clause');
        }

        $tableName = $this->table;
        if (!is_null($dbHelper)) {
            $tableName = $dbHelper->delimiterTable($tableName);
        }

        $joinTables = $this->getJoinTables($dbHelper);
        $joinBeforeSet = $joinTables['position'] === 'before_set' ? $joinTables['sql'] : '';
        $joinAfterSet = $joinTables['position'] === 'after_set' ? $joinTables['sql'] : '';

        $sql = 'UPDATE ' . $tableName
            . $joinBeforeSet
            . ' SET ' . implode(', ', $fieldsStr)
            . $joinAfterSet
            . ' WHERE ' . $whereStr[0];

        $params = array_merge($params, $whereStr[1]);

        $sql = ORMHelper::processLiteral($sql, $params);
        return new SqlStatement($sql, $params);
    }

    #[Override]
    public function convert(?DbFunctionsInterface $dbDriver = null): QueryBuilderInterface
    {
        $query = Query::getInstance()
            ->fields(array_keys($this->set))
            ->table($this->table);

        foreach ($this->where as $item) {
            $query->where($item['filter'], $item['params']);
        }

        foreach ($this->joinTables as $joinTable) {
            $query->join($joinTable['table'], $joinTable['condition']);
        }

        return $query;
    }
}
