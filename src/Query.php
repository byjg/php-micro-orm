<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\AnyDataset\Db\SqlStatement;
use ByJG\MicroOrm\Exception\InvalidArgumentException;
use Override;

class Query extends QueryBasic
{
    protected array $groupBy = [];
    protected array $having = [];
    protected array $orderBy = [];
    protected ?int $limitStart = null;
    protected ?int $limitEnd = null;
    protected ?int $top = null;
    protected bool $forUpdate = false;

    #[Override]
    public static function getInstance(): Query
    {
        return new Query();
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
     * Example:
     *     $query->orderBy(['price desc']);
     *
     * @param array $fields
     * @return $this
     */
    public function orderBy(array $fields): static
    {
        $this->orderBy = array_merge($this->orderBy, $fields);

        return $this;
    }

    public function forUpdate(): static
    {
        $this->forUpdate = true;

        return $this;
    }

    /**
     * @param int $start
     * @param int $end
     * @return $this
     * @throws InvalidArgumentException
     */
    public function limit(int $start, int $end): static
    {
        if (!is_null($this->top)) {
            throw new InvalidArgumentException('You cannot mix TOP and LIMIT');
        }
        $this->limitStart = $start;
        $this->limitEnd = $end;
        return $this;
    }

    /**
     * @param int $top
     * @return $this
     * @throws InvalidArgumentException
     */
    public function top(int $top): static
    {
        if (!is_null($this->limitStart)) {
            throw new InvalidArgumentException('You cannot mix TOP and LIMIT');
        }
        $this->top = $top;
        return $this;
    }


    /**
     * @param DbDriverInterface|null $dbDriver
     * @return SqlStatement
     * @throws InvalidArgumentException
     */
    #[Override]
    public function build(?DbDriverInterface $dbDriver = null): SqlStatement
    {
        $buildResult = parent::build($dbDriver);
        $sql = $buildResult->getSql();
        $params = $buildResult->getParams();

        $sql .= $this->addGroupBy();

        $sql .= $this->addHaving();

        $sql .= $this->addOrderBy();

        $sql = $this->addForUpdate($dbDriver, $sql);

        $sql = $this->addTop($dbDriver, $sql);

        $sql = $this->addLimit($dbDriver, $sql);

        $sql = ORMHelper::processLiteral($sql, $params);

        return new SqlStatement($sql, $params);
    }

    protected function addOrderBy(): string
    {
        if (empty($this->orderBy)) {
            return "";
        }
        return ' ORDER BY ' . implode(', ', $this->orderBy);
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
     * @param string $sql
     * @return string
     * @throws InvalidArgumentException
     */
    protected function addForUpdate(?DbDriverInterface $dbDriver, string $sql): string
    {
        if (!$this->forUpdate) {
            return $sql;
        }

        if (is_null($dbDriver)) {
            throw new InvalidArgumentException('To get FOR UPDATE working you have to pass the DbDriver');
        }

        return $dbDriver->getDbHelper()->forUpdate($sql);
    }

    /**
     * @param DbDriverInterface|null $dbDriver
     * @param string $sql
     * @return string
     * @throws InvalidArgumentException
     */
    protected function addTop(?DbDriverInterface $dbDriver, string $sql): string
    {
        if (empty($this->top)) {
            return $sql;
        }

        if (is_null($dbDriver)) {
            throw new InvalidArgumentException('To get Limit and Top working you have to pass the DbDriver');
        }

        return $dbDriver->getDbHelper()->top($sql, $this->top);
    }

    /**
     * @param DbDriverInterface|null $dbDriver
     * @param string $sql
     * @return string
     * @throws InvalidArgumentException
     */
    protected function addLimit(?DbDriverInterface $dbDriver, string $sql): string
    {
        if (empty($this->limitStart) && ($this->limitStart !== 0)) {
            return $sql;
        }

        if (is_null($dbDriver)) {
            throw new InvalidArgumentException('To get Limit and Top working you have to pass the DbDriver');
        }

        return $dbDriver->getDbHelper()->limit($sql, $this->limitStart, $this->limitEnd);
    }

    /**
     * @throws InvalidArgumentException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     */
    public function getQueryBasic(): QueryBasic
    {
        $queryBasic = new QueryBasic();
        $queryBasic->fields($this->fields);
        $queryBasic->table($this->table, $this->alias);

        foreach ($this->where as $where) {
            $queryBasic->where($where['filter'], $where['params']);
        }

        foreach ($this->join as $join) {
            if ($join['type'] == 'INNER') {
                $queryBasic->join($join['table'], $join['filter'], $join['alias']);
            } else if ($join['type'] == 'LEFT') {
                $queryBasic->leftJoin($join['table'], $join['filter'], $join['alias']);
            } else if ($join['type'] == 'RIGHT') {
                $queryBasic->rightJoin($join['table'], $join['filter'], $join['alias']);
            } else if ($join['type'] == 'CROSS') {
                $queryBasic->crossJoin($join['table'], $join['alias']);
            }
        }

        if (!is_null($this->recursive)) {
            $queryBasic->withRecursive($this->recursive);
        }

        if ($this->distinct) {
            $queryBasic->distinct();
        }

        return $queryBasic;
    }
}
