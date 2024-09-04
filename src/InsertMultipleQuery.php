<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DbFunctionsInterface;
use ByJG\MicroOrm\Exception\OrmInvalidFieldsException;
use ByJG\MicroOrm\Literal\LiteralInterface;

class InsertMultipleQuery extends Updatable
{
    protected $fields = [];
    protected $row = [];

    public static function getInstance(string $table = null, array $fields = []): self
    {
        $query = new InsertMultipleQuery();
        if (!is_null($table)) {
            $query->table($table);
        }

        $query->fields($fields);

        return $query;
    }

    public function fields(array $fields)
    {
        $this->fields = $fields;
        $this->row = [];
        return $this;
    }

    public function addRow($row)
    {
        if (count($this->fields) !== count($row)) {
            throw new \InvalidArgumentException('The row must have the same number of fields');
        }

        $rowToAdd = [];
        foreach ($this->fields as $field) {
            if (!array_key_exists($field, $row)) {
                throw new \InvalidArgumentException('The row must have the same fields');
            }
            $rowToAdd[$field] = $row[$field];
        }

        $this->row[] = $rowToAdd;
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
            throw new OrmInvalidFieldsException('You must specify the fields for insert');
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
            . ' ( ' . implode(', ', $fieldsStr) . ' ) '
            . ' values ';

        $params = [];
        $rowNum = 1;
        foreach ($this->row as $row) {
            $paramRow = [];
            foreach ($row as $key => $value) {
                $paramRow[$key . $rowNum] = $value;
            }
            $sql .= ' ( :' . implode(', :', array_keys($paramRow)) . ' ),';
            $params = array_merge($params, $paramRow);
            $rowNum++;
        }

        return ORMHelper::processLiteral(trim($sql, ","), $params);
    }

    public function convert(?DbFunctionsInterface $dbDriver = null): QueryBuilderInterface
    {
        throw new \InvalidArgumentException('It is not possible to convert an InsertMultipleQuery to a Query');
    }
}
