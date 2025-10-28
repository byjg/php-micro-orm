<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DbFunctionsInterface;
use ByJG\AnyDataset\Db\SqlStatement;
use ByJG\MicroOrm\Exception\OrmInvalidFieldsException;
use ByJG\MicroOrm\Interface\QueryBuilderInterface;
use InvalidArgumentException;
use Override;

class InsertSelectQuery extends Updatable
{
    protected array $fields = [];

    protected ?QueryBuilderInterface $query = null;

    protected ?SqlStatement $sqlStatement = null;


    public static function getInstance(?string $table = null, array $fields = []): self
    {
        $query = new InsertSelectQuery();
        if (!is_null($table)) {
            $query->table($table);
        }

        $query->fields($fields);

        return $query;
    }

    public function fields(array $fields): static
    {
        $this->fields = $fields;
        return $this;
    }

    public function fromQuery(QueryBuilderInterface $query): static
    {
        $this->query = $query;

        return $this;
    }

    public function fromSqlStatement(SqlStatement $sqlStatement): static
    {
        $this->sqlStatement = $sqlStatement;

        return $this;
    }

    /**
     * @param DbFunctionsInterface|null $dbHelper
     * @return SqlStatement
     * @throws OrmInvalidFieldsException
     */
    #[Override]
    public function build(?DbFunctionsInterface $dbHelper = null): SqlStatement
    {
        if (empty($this->fields)) {
            throw new OrmInvalidFieldsException('You must specify the fields for insert');
        }

        if (empty($this->query) && empty($this->sqlStatement)) {
            throw new OrmInvalidFieldsException('You must specify the query for insert');
        } elseif (!empty($this->query) && !empty($this->sqlStatement)) {
            throw new OrmInvalidFieldsException('You must specify only one query for insert');
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
            . ' ( ' . implode(', ', $fieldsStr) . ' ) ';

        if (!is_null($this->sqlStatement)) {
            $fromObj = $this->sqlStatement;
        } else {
            $fromObj = $this->query->build();
        }

        return new SqlStatement($sql . $fromObj->getSql(), $fromObj->getParams());
    }

    #[Override]
    public function convert(?DbFunctionsInterface $dbDriver = null): QueryBuilderInterface
    {
        throw new InvalidArgumentException('It is not possible to convert an InsertSelectQuery to a Query');
    }
}
