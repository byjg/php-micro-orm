<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\Interfaces\DbDriverInterface;
use ByJG\AnyDataset\Db\Interfaces\SqlDialectInterface;
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
     * @param DbDriverInterface|SqlDialectInterface|null $dbDriverOrHelper
     * @return SqlStatement
     * @throws OrmInvalidFieldsException
     */
    #[Override]
    public function build(SqlDialectInterface|DbDriverInterface|null $dbDriverOrHelper = null): SqlStatement
    {
        if (empty($this->fields)) {
            throw new OrmInvalidFieldsException('You must specify the fields for insert');
        }

        $dbDriver = null;
        $dbHelper = $dbDriverOrHelper;
        if ($dbDriverOrHelper instanceof DbDriverInterface) {
            $dbDriver = $dbDriverOrHelper;
            $dbHelper = $dbDriverOrHelper->getSqlDialect();
        }

        if (empty($this->query) && empty($this->sqlStatement)) {
            throw new OrmInvalidFieldsException('You must specify the query for insert');
        } elseif (!empty($this->query) && !empty($this->sqlStatement)) {
            throw new OrmInvalidFieldsException('You must specify only one query for insert');
        }

        $fieldsStr = $this->fields;
        if ($dbHelper instanceof SqlDialectInterface) {
            $fieldsStr = $dbHelper->delimiterField($fieldsStr);
        }

        $tableStr = $this->table;
        if ($dbHelper instanceof SqlDialectInterface) {
            $tableStr = $dbHelper->delimiterTable($tableStr);
        }

        $sql = 'INSERT INTO '
            . $tableStr
            . ' ( ' . implode(', ', $fieldsStr) . ' ) ';

        if (!is_null($this->sqlStatement)) {
            $fromObj = $this->sqlStatement;
        } elseif ($this->query !== null) {
            $fromObj = $this->query->build($dbDriver);
        } else {
            throw new OrmInvalidFieldsException('Query or SqlStatement must be set');
        }

        return new SqlStatement($sql . $fromObj->getSql(), $fromObj->getParams());
    }

    #[Override]
    public function convert(?SqlDialectInterface $dbHelper = null): QueryBuilderInterface
    {
        throw new InvalidArgumentException('It is not possible to convert an InsertSelectQuery to a Query');
    }
}
