<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Core\GenericIterator;
use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\AnyDataset\Db\Interfaces\DbDriverInterface;
use ByJG\AnyDataset\Db\SqlStatement;
use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\MicroOrm\Interface\QueryBuilderInterface;
use ByJG\Serializer\Serialize;
use Override;

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
    protected array $groupBy = [];
    protected array $having = [];

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
        $serialized = Serialize::from($entity)->withStopAtFirstLevel()->toArray();

        foreach (array_keys($serialized) as $fieldName) {
            $fieldMapping = $mapper->getFieldMap($fieldName);
            if (empty($fieldMapping) || is_array($fieldMapping)) {
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

    /**
     * Add an INNER JOIN to $table using the registered parentTable relationships,
     * deriving the ON condition instead of writing it by hand. $table is connected to
     * a table already in the query (base or a previous join); if they are not directly
     * related, the intermediate tables on the shortest relationship path are joined too
     * — so you don't have to remember them — while tables already in the query are
     * skipped. The query keeps its own base table, so this composes on top of an
     * existing query, including one a repository has scoped to its table.
     *
     * Example:
     *    $query->table('task')->joinRelated('project');
     *    // INNER JOIN project ON project.id = task.project_id
     *
     *    $query->table('project')->joinRelated('note');
     *    // auto-joins task in between: INNER JOIN task ON … INNER JOIN note ON …
     *
     * For aliased joins, use join()/leftJoin()/rightJoin() with an explicit ON.
     *
     * @throws InvalidArgumentException When no relationship path connects $table to the query.
     */
    public function joinRelated(string $table): static
    {
        return $this->addRelatedJoin($table, 'INNER');
    }

    /**
     * Like joinRelated(), but every hop it adds is a LEFT JOIN.
     *
     * @throws InvalidArgumentException When no relationship path connects $table to the query.
     */
    public function leftJoinRelated(string $table): static
    {
        return $this->addRelatedJoin($table, 'LEFT');
    }

    /**
     * Like joinRelated(), but every hop it adds is a RIGHT JOIN.
     *
     * @throws InvalidArgumentException When no relationship path connects $table to the query.
     */
    public function rightJoinRelated(string $table): static
    {
        return $this->addRelatedJoin($table, 'RIGHT');
    }

    /**
     * Walk the relationship path from a table already in the query to $table and join
     * each hop's not-yet-present table, delegating to join()/leftJoin()/rightJoin().
     *
     * @throws InvalidArgumentException When no relationship path connects $table to the query.
     */
    private function addRelatedJoin(string $table, string $type): static
    {
        foreach ($this->relatedTables() as $present) {
            $path = ORM::getRelationshipData($present, $table);
            if (empty($path)) {
                continue;
            }

            foreach ($path as $rel) {
                $current = $this->relatedTables();
                $parentPresent = in_array($rel['parent'], $current, true);
                if ($parentPresent && in_array($rel['child'], $current, true)) {
                    continue; // both tables already in the query
                }
                $newTable = $parentPresent ? $rel['child'] : $rel['parent'];
                $on = "{$rel['parent']}.{$rel['pk']} = {$rel['child']}.{$rel['fk']}";
                match ($type) {
                    'LEFT' => $this->leftJoin($newTable, $on),
                    'RIGHT' => $this->rightJoin($newTable, $on),
                    default => $this->join($newTable, $on),
                };
            }

            return $this;
        }

        throw new InvalidArgumentException("No relationship registered between '$table' and the query tables.");
    }

    /**
     * Table names already present in the query (base + joined), used to resolve a
     * relationship for joinRelated().
     *
     * @return string[]
     */
    private function relatedTables(): array
    {
        $tables = [];
        if (is_string($this->table) && $this->table !== '') {
            $tables[] = $this->table;
        }
        foreach ($this->join as $item) {
            if (is_string($item['table'])) {
                $tables[] = $item['table'];
            }
        }
        return $tables;
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
     * Example:
     *    $query->groupBy(['name']);
     *
     * @param array $fields
     * @return $this
     */
    public function groupBy(array $fields): static
    {
        $this->groupBy = array_merge($this->groupBy, $fields);

        return $this;
    }

    /**
     * Example:
     *    $query->having('count(price) > 10');
     *
     * @param string $filter
     * @return $this
     */
    public function having(string $filter): static
    {
        $this->having[] = $filter;
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
                $params = array_merge($params, $subQuery->getParams() ?? []);
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
            if (!empty($subQuery->getParams()) && !$supportParams) {
                throw new InvalidArgumentException("SubQuery does not support filters");
            }
            if (empty($alias) || $alias instanceof QueryBasic) {
                throw new InvalidArgumentException("SubQuery requires you define an alias");
            }
            $table = "({$subQuery->getSql()})";
            $params = $subQuery->getParams();
        }
        $aliasStr = is_string($alias) ? $alias : '';
        return [$table . (!empty($aliasStr) && $table != $aliasStr ? " as " . $aliasStr : ""), $params];
    }

    protected function addGroupBy(): string
    {
        if (empty($this->groupBy)) {
            return "";
        }
        return ' GROUP BY ' . implode(', ', $this->groupBy);
    }

    protected function addHaving(): string
    {
        if (empty($this->having)) {
            return "";
        }
        return ' HAVING ' . implode(' AND ', $this->having);
    }

    /**
     * @param DbDriverInterface|null $dbDriver
     * @return SqlStatement
     * @throws InvalidArgumentException
     */
    #[Override]
    public function build(?DbDriverInterface $dbDriver = null): SqlStatement
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

        $sql .= $this->addGroupBy();

        $sql .= $this->addHaving();

        $sql = ORMHelper::processLiteral($sql, $params);

        return new OrmSqlStatement($sql, $params);
    }

    #[Override]
    public function buildAndGetIterator(DatabaseExecutor $executor, ?CacheQueryResult $cache = null): GenericIterator
    {
        $sqlStatement = $this->build($executor->getDriver());
        if (!empty($cache)) {
            $sqlStatement = $sqlStatement->withCache($cache->getCache(), $cache->getCacheKey(), $cache->getTtlInSeconds());
        }
        return $executor->getIterator($sqlStatement);
    }
}
