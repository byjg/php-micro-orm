<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DbFunctionsInterface;
use ByJG\MicroOrm\Exception\OrmInvalidFieldsException;

class InsertMultipleQuery extends Updatable
{
    protected array $fields = [];
    protected array $row = [];

    public static function getInstance(string $table = null, array $fields = []): self
    {
        $query = new InsertMultipleQuery();
        if (!is_null($table)) {
            $query->table($table);
        }

        $query->fields($fields);

        return $query;
    }

    public function fields(array $fields): static
    {
        $this->fields = $fields;
        $this->row = [];
        return $this;
    }

    public function addRow(array $row): static
    {
        if (count($this->fields) !== count($row)) {
            throw new \InvalidArgumentException('The row must have the same number of fields');
        }

        $rowToAdd = [];
        foreach ($this->fields as $field) {
            if (!array_key_exists($field, $row)) {
                throw new \InvalidArgumentException("The field '$field' must be in the row");
            }
            $rowToAdd[$field] = $row[$field];
        }

        $this->row[] = $rowToAdd;

        return $this;
    }

    /**
     * @param DbFunctionsInterface|null $dbHelper
     * @return SqlObject
     * @throws OrmInvalidFieldsException
     */
    public function build(DbFunctionsInterface $dbHelper = null): SqlObject
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

        $sql = ORMHelper::processLiteral(trim($sql, ","), $params);

        return new SqlObject($sql, $params);
    }

    public function convert(?DbFunctionsInterface $dbDriver = null): QueryBuilderInterface
    {
        throw new \InvalidArgumentException('It is not possible to convert an InsertMultipleQuery to a Query');
    }
}
