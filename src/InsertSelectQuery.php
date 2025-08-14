<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\AnyDataset\Db\DbFunctionsInterface;
use ByJG\MicroOrm\Exception\OrmInvalidFieldsException;
use ByJG\MicroOrm\Interface\QueryBuilderInterface;
use InvalidArgumentException;

class InsertSelectQuery extends Updatable
{
    protected array $fields = [];

    protected ?QueryBuilderInterface $query = null;

    protected ?SqlObject $sqlObject = null;


    public static function getInstance(string $table = null, array $fields = []): self
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

    public function fromSqlObject(SqlObject $sqlObject): static
    {
        $this->sqlObject = $sqlObject;

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

        if (empty($this->query) && empty($this->sqlObject)) {
            throw new OrmInvalidFieldsException('You must specify the query for insert');
        } elseif (!empty($this->query) && !empty($this->sqlObject)) {
            throw new OrmInvalidFieldsException('You must specify only one query for insert');
        }

        $fieldsStr = $this->fields;
        if (!is_null($dbDriverOrHelper)) {
            $fieldsStr = $dbDriverOrHelper->delimiterField($fieldsStr);
        }

        $tableStr = $this->table;
        if (!is_null($dbDriverOrHelper)) {
            $tableStr = $dbDriverOrHelper->delimiterTable($tableStr);
        }

        $sql = 'INSERT INTO '
            . $tableStr
            . ' ( ' . implode(', ', $fieldsStr) . ' ) ';

        if (!is_null($this->sqlObject)) {
            $fromObj = $this->sqlObject;
        } else {
            $fromObj = $this->query->build();
        }

        return new SqlObject($sql . $fromObj->getSql(), $fromObj->getParameters());
    }

    public function convert(?DbFunctionsInterface $dbDriver = null): QueryBuilderInterface
    {
        throw new InvalidArgumentException('It is not possible to convert an InsertSelectQuery to a Query');
    }
}
