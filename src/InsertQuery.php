<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\AnyDataset\Db\DbFunctionsInterface;
use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\MicroOrm\Exception\OrmInvalidFieldsException;
use ByJG\MicroOrm\Interface\QueryBuilderInterface;
use ByJG\MicroOrm\Literal\LiteralInterface;

class InsertQuery extends Updatable
{
    protected array $values = [];

    public static function getInstance(string $table = null, array $fieldsAndValues = []): self
    {
        $query = new InsertQuery();
        if (!is_null($table)) {
            $query->table($table);
        }

        foreach ($fieldsAndValues as $field => $value) {
            $query->set($field, $value);
        }

        return $query;
    }

    /**
     * Fields to be used for the INSERT
     *
     * Example:
     *   $query->set('name', 'price');
     *
     * @param string $field
     * @param int|float|bool|string|LiteralInterface|null $value
     * @return $this
     */
    public function set(string $field, int|float|bool|string|LiteralInterface|null $value): self
    {
        $this->values[$field] = $value;
        return $this;
    }

    /**
     * Fields to be used for the INSERT
     * Use this method to add the fields will be prepared for the INSERT - same query, but with different values, executed multiple times
 * Example:
     *   $query->fields(['name', 'price']);
     *
     * @param array $fields
     * @return $this
     */
    public function defineFields(array $fields): static
    {
        // swap the key and value of the $fields array and set null as value
        $fields = array_flip($fields);
        $fields = array_map(function ($item) {
            return null;
        }, $fields);

        $this->values = array_merge($this->values, $fields);

        return $this;
    }

    /**
     * @param DbDriverInterface|DbFunctionsInterface|null $dbDriverOrHelper
     * @return SqlObject
     * @throws OrmInvalidFieldsException
     */
    public function build(DbFunctionsInterface|DbDriverInterface|null $dbDriverOrHelper = null): SqlObject
    {
        if (empty($this->values)) {
            throw new OrmInvalidFieldsException('You must specify the fields for insert');
        }

        if ($dbDriverOrHelper instanceof DbDriverInterface) {
            $dbDriverOrHelper = $dbDriverOrHelper->getDbHelper();
        }

        $fieldsStr = array_keys($this->values); // get the fields from the first element only
        if (!is_null($dbDriverOrHelper)) {
            $fieldsStr = $dbDriverOrHelper->delimiterField($fieldsStr);
        }

        $tableStr = $this->table;
        if (!is_null($dbDriverOrHelper)) {
            $tableStr = $dbDriverOrHelper->delimiterTable($tableStr);
        }

        $sql = 'INSERT INTO '
            . $tableStr
            . '( ' . implode(', ', $fieldsStr) . ' ) '
            . ' values '
            . '( :' . implode(', :', array_keys($this->values)) . ' ) ';

        $params = $this->values;
        $sql = ORMHelper::processLiteral($sql, $params);
        return new SqlObject($sql, $params, SqlObjectEnum::INSERT);
    }

    /**
     * @throws InvalidArgumentException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     */
    public function convert(?DbFunctionsInterface $dbDriver = null): QueryBuilderInterface
    {
        $query = Query::getInstance()
            ->fields(array_keys($this->values))
            ->table($this->table);

        foreach ($this->where as $item) {
            $query->where($item['filter'], $item['params']);
        }

        return $query;
    }
}
