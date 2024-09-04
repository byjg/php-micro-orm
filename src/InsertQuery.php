<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DbFunctionsInterface;
use ByJG\MicroOrm\Exception\OrmInvalidFieldsException;
use ByJG\MicroOrm\Literal\LiteralInterface;

class InsertQuery extends Updatable
{
    protected $fields = [];

    public static function getInstance(string $table = null, array $fields = []): self
    {
        $query = new InsertQuery();
        if (!is_null($table)) {
            $query->table($table);
        }

        foreach ($fields as $field => $value) {
            $query->field($field, $value);
        }

        return $query;
    }

    /**
     * Fields to be used for the INSERT
     * Example:
     *   $query->fields(['name', 'price']);
     *
     * @param array $fields
     * @return $this
     */
    public function field(string $field, int|float|bool|string|LiteralInterface|null $value): self
    {
        $this->fields[$field] = $value;
        return $this;
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
        // swap the key and value of the $fields array and set null as value
        $fields = array_flip($fields);
        $fields = array_map(function ($item) {
            return null;
        }, $fields);

        $this->fields = array_merge($this->fields, $fields);

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
            . '( :' . implode(', :', array_keys($this->fields)) . ' ) ';

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
