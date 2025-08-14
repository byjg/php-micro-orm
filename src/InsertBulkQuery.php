<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\AnyDataset\Db\DbFunctionsInterface;
use ByJG\MicroOrm\Exception\OrmInvalidFieldsException;
use ByJG\MicroOrm\Interface\QueryBuilderInterface;
use ByJG\MicroOrm\Literal\Literal;
use InvalidArgumentException;

class InsertBulkQuery extends Updatable
{
    protected array $fields = [];

    protected ?QueryBuilderInterface $query = null;

    protected ?SqlObject $sqlObject = null;

    protected bool $safe = false;

    public function __construct(string $table, array $fieldNames)
    {
        $this->table($table);

        foreach ($fieldNames as $fieldname) {
            $this->fields[$fieldname] = [];
        }
    }


    public static function getInstance(string $table, array $fieldNames): static
    {
        return new InsertBulkQuery($table, $fieldNames);
    }

    public function withSafeParameters(): static
    {
        $this->safe = true;
        return $this;
    }

    /**
     * @throws OrmInvalidFieldsException
     */
    public function values(array $values, bool $allowNonMatchFields = true): static
    {
        if (array_diff(array_keys($this->fields), array_keys($values))) {
            throw new OrmInvalidFieldsException('The provided values do not match the expected fields');
        }

        if (!$allowNonMatchFields && array_diff(array_keys($values), array_keys($this->fields))) {
            throw new OrmInvalidFieldsException('The provided values contain more fields than expected');
        }

        foreach (array_keys($this->fields) as $field) {
            $this->fields[$field][] = $values[$field];
        }

        return $this;
    }

    /**
     * @param DbDriverInterface|DbFunctionsInterface|null $dbDriverOrHelper
     * @return SqlObject
     * @throws OrmInvalidFieldsException
     */
    public function build(DbFunctionsInterface|DbDriverInterface|null $dbDriverOrHelper = null): SqlObject
    {
        if (empty($this->fields)) {
            throw new OrmInvalidFieldsException('You must specify the fields for insert');
        }

        if ($dbDriverOrHelper instanceof DbDriverInterface) {
            $dbDriverOrHelper = $dbDriverOrHelper->getDbHelper();
        }

        $tableStr = $this->table;
        if (!is_null($dbDriverOrHelper)) {
            $tableStr = $dbDriverOrHelper->delimiterTable($tableStr);
        }

        // Extract column names
        $columns = array_keys($this->fields);

        // Get the number of rows
        $rowCount = count(current($this->fields));

        // Initialize placeholders and parameters
        $placeholders = [];
        $params = [];

        // Build placeholders and populate $params
        for ($i = 0; $i < $rowCount; $i++) {
            $rowPlaceholders = [];
            foreach ($columns as $j => $col) {
                $paramKey = "p{$i}_$j"; // Generate the parameter key
                $rowPlaceholders[] = ":$paramKey"; // Add to row placeholders
                if ($this->safe) {
                    $params[$paramKey] = $this->fields[$col][$i];
                } else {
                    $value = str_replace("'", "''", $this->fields[$col][$i]);
                    if (!is_numeric($value)) {
                        $value = $dbDriverOrHelper?->delimiterField($value) ?? "'{$value}'";
                    }
                    $params[$paramKey] = new Literal($value); // Map parameter key to value
                }
            }
            $placeholders[] = '(' . implode(', ', $rowPlaceholders) . ')'; // Add row placeholders to query
        }

        if (!is_null($dbDriverOrHelper)) {
            $columns = $dbDriverOrHelper->delimiterField($columns);
        }

        // Construct the final SQL query
        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES %s",
            $tableStr,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $sql = ORMHelper::processLiteral($sql, $params);
        return new SqlObject($sql, $params);
    }

    public function convert(?DbFunctionsInterface $dbDriver = null): QueryBuilderInterface
    {
        throw new InvalidArgumentException('It is not possible to convert an InsertBulkQuery to a Query');
    }
}
