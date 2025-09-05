<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Core\GenericIterator;
use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\AnyDataset\Db\SqlStatement;
use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\MicroOrm\Interface\QueryBuilderInterface;
use ByJG\Serializer\Serialize;

class QueryBasic implements QueryBuilderInterface
{
    use WhereTrait;

    protected array $fields = [];
    protected QueryBasic|string $table = "";
    protected ?string $alias = "";
    protected array $join = [];
    protected DbDriverInterface|null $dbDriver = null;
    protected ?Recursive $recursive = null;
    protected bool $distinct = false;

    public static function getInstance(): QueryBasic
    {
        return new QueryBasic();
    }

    /**
     * Example:
     *   $query->fields(['name', 'price']);
     *
     * @param array $fields
     * @return $this
     * @throws InvalidArgumentException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     */
    public function fields(array $fields): static
    {
        foreach ($fields as $field) {
            $this->field($field);
        }

        return $this;
    }

    /**
     * @throws InvalidArgumentException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     */
    public function field(Mapper|QueryBasic|string $field, ?string $alias = null): static
    {
        if ($field instanceof Mapper) {
            $this->addFieldFromMapper($field);
            return $this;
        }

        if ($field instanceof QueryBasic && empty($alias)) {
            throw new InvalidArgumentException("You must define an alias for the sub query");
        }

        if (!empty($alias)) {
            $this->fields[$alias] = $field;
        } else {
            $this->fields[] = $field;
        }

        return $this;
    }

    /**
     * @param Mapper $mapper
     * @throws InvalidArgumentException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     */
    protected function addFieldFromMapper(Mapper $mapper): void
    {
        $entityClass = $mapper->getEntity();
        $entity = new $entityClass();
        $serialized = Serialize::from($entity)->toArray();

        foreach (array_keys($serialized) as $fieldName) {
            $fieldMapping = $mapper->getFieldMap($fieldName);
            if (empty($fieldMapping)) {
                $mapField = $fieldName;
                $alias = null;
            } else {
                if (!$fieldMapping->isSyncWithDb()) {
                    continue;
                }
                $mapField = $fieldMapping->getFieldName();
                $alias = $fieldMapping->getFieldAlias();
            }

            $this->field($mapper->getTable() . '.' . $mapField, $alias);
        }
    }

    /**
     * Example
     *    $query->table('product');
     *
     * @param QueryBasic|string $table
     * @param string|null $alias
     * @return $this
     */
    public function table(QueryBasic|string $table, ?string $alias = null): static
    {
        $this->table = $table;
        $this->alias = $alias;

        return $this;
    }

    /**
     * Example:
     *    $query->join('sales', 'product.id = sales.id');
     *
     * @param string|QueryBasic $table
     * @param string $filter
     * @param string|null $alias
     * @return $this
     */
    public function join(QueryBasic|string $table, string $filter, ?string $alias = null): static
    {
        $this->join[] = [ 'table'=>$table, 'filter'=>$filter, 'type' => 'INNER', 'alias' => empty($alias) ? $table : $alias];
        return $this;
    }

    /**
     * Example:
     *    $query->leftJoin('sales', 'product.id = sales.id');
     *
     * @param string|QueryBasic $table
     * @param string $filter
     * @param string|null $alias
     * @return $this
     */
    public function leftJoin(QueryBasic|string $table, string $filter, ?string $alias = null): static
    {
        $this->join[] = [ 'table'=>$table, 'filter'=>$filter, 'type' => 'LEFT', 'alias' => empty($alias) ? $table : $alias];
        return $this;
    }

    /**
     * Example:
     *    $query->rightJoin('sales', 'product.id = sales.id');
     *
     * @param string|QueryBasic $table
     * @param string $filter
     * @param string|null $alias
     * @return $this
     */
    public function rightJoin(QueryBasic|string $table, string $filter, ?string $alias = null): static
    {
        $this->join[] = [ 'table'=>$table, 'filter'=>$filter, 'type' => 'RIGHT', 'alias' => empty($alias) ? $table : $alias];
        return $this;
    }

    public function crossJoin(QueryBasic|string $table, ?string $alias = null): static
    {
        $this->join[] = [ 'table'=>$table, 'filter'=>'', 'type' => 'CROSS', 'alias' => empty($alias) ? $table : $alias];
        return $this;
    }

    public function withRecursive(Recursive $recursive): static
    {
        $this->recursive = $recursive;
        if (empty($this->table)) {
            $this->table($recursive->getTableName());
        }
        return $this;
    }

    /**
     * Add DISTINCT keyword to the query
     *
     * @return $this
     */
    public function distinct(): static
    {
        $this->distinct = true;
        return $this;
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function getFields(): array
    {
        if (empty($this->fields)) {
            return [' * ', [] ];
        }

        $fieldList = '';
        $params = [];
        foreach ($this->fields as $alias => $field) {
            if (!empty($fieldList)) {
                $fieldList .= ', ';
            }
            if (is_numeric($alias)) {
                $fieldList .= $field;
            } elseif ($field instanceof QueryBasic) {
                $subQuery = $field->build($this->dbDriver);
                $fieldList .= '(' . $subQuery->getSql() . ') as ' . $alias;
                $params = array_merge($params, $subQuery->getParameters());
            } else {
                $fieldList .= $field . ' as ' . $alias;
            }
        }

        return [' ' . $fieldList . ' ', $params ];
    }

    /**
     * @return array
     * @throws InvalidArgumentException
     */
    protected function getJoin(): array
    {
        [ $joinStr, $params ] = $this->buildTable($this->table, $this->alias);
        foreach ($this->join as $item) {
            [ $table, $moreParams ] = $this->buildTable($item['table'], $item['alias'], false);
            $joinStr .= ' ' . $item['type'] . " JOIN $table";
            if (!empty($item['filter'])) {
                $joinStr .= " ON " . $item['filter'];
            }
            $params = array_merge($params, $moreParams);
        }
        return [ $joinStr, $params ];
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function buildTable(QueryBasic|string $table, QueryBasic|string|null $alias, bool $supportParams = true): array
    {
        $params = [];
        if ($table instanceof QueryBasic) {
            $subQuery = $table->build($this->dbDriver);
            if (!empty($subQuery->getParameters()) && !$supportParams) {
                throw new InvalidArgumentException("SubQuery does not support filters");
            }
            if (empty($alias) || $alias instanceof QueryBasic) {
                throw new InvalidArgumentException("SubQuery requires you define an alias");
            }
            $table = "({$subQuery->getSql()})";
            $params = $subQuery->getParameters();
        }
        return [ $table . (!empty($alias) && $table != $alias ? " as " . $alias : ""), $params ];
    }   

    /**
     * @param DbDriverInterface|null $dbDriver
     * @return SqlObject
     * @throws InvalidArgumentException
     */
    public function build(?DbDriverInterface $dbDriver = null): SqlObject
    {
        $this->dbDriver = $dbDriver;

        $sql = "";
        if (!empty($this->recursive)) {
            $sql = $this->recursive->build($dbDriver)->getSql();
        }

        [ $fieldList , $params ] = $this->getFields();
        [ $tableList , $paramsTable ] = $this->getJoin();

        $params = array_merge($params, $paramsTable);

        $sql .= "SELECT " .
            ($this->distinct ? "DISTINCT " : "") .
            $fieldList .
            (!empty($tableList) ? "FROM " . $tableList : "");

        $whereStr = $this->getWhere();
        if (!is_null($whereStr)) {
            $sql .= ' WHERE ' . $whereStr[0];
            $params = array_merge($params, $whereStr[1]);
        }

        $sql = ORMHelper::processLiteral($sql, $params);

        return new SqlObject($sql, $params);
    }

    public function buildAndGetIterator(?DbDriverInterface $dbDriver = null, ?CacheQueryResult $cache = null): GenericIterator
    {
        $sqlObject = $this->build($dbDriver);
        $sqlStatement = new SqlStatement($sqlObject->getSql());
        if (!empty($cache)) {
            $sqlStatement->withCache($cache->getCache(), $cache->getCacheKey(), $cache->getTtl());
        }
        return $sqlStatement->getIterator($dbDriver, $sqlObject->getParameters());
    }
}
