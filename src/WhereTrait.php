<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Core\IteratorFilter;
use ByJG\AnyDataset\Db\IteratorFilterSqlFormatter;

trait WhereTrait
{
    protected array $where = [];
    protected bool $unsafe = false;
    private static int $whereInCounter = 0;

    /**
     * Example:
     *    $query->filter('price > [[amount]]', [ 'amount' => 1000] );
     *
     * @param string|IteratorFilter $filter
     * @param array $params
     * @return $this
     */
    public function where(string|IteratorFilter $filter, array $params = []): static
    {
        if ($filter instanceof IteratorFilter) {
            $formatter = new IteratorFilterSqlFormatter();
            $filter = $formatter->getFilter($filter->getRawFilters(), $params);
        }

        $this->where[] = [ 'filter' => $filter, 'params' => $params  ];
        return $this;
    }

    protected function getWhere(): ?array
    {
        $where = $this->where;

        if (!$this->unsafe) {
            $tableList = [];
            $from = $this->alias ?? $this->table;
            if (!($from instanceof QueryBasic) && ORM::getMapper($from)?->isSoftDeleteEnabled() === true) {
                $tableList[] = $from;
                $where[] = ["filter" => "{$from}.deleted_at is null", "params" => []];
            }

            /** @psalm-suppress RedundantCondition This is a Trait, and $this->join is defined elsewhere */
            if (isset($this->join)) {
                foreach ($this->join as $item) {
                    if ($item['table'] instanceof QueryBasic) {
                        continue;
                    }

                    $tableName = $item["alias"] ?? $item['table'];
                    if (!in_array($tableName, $tableList) && ORM::getMapper($item['table'])?->isSoftDeleteEnabled() === true) {
                        $tableList[] = $tableName;
                        $where[] = ["filter" => "{$tableName}.deleted_at is null", "params" => []];
                    }
                }
            }
        }

        $whereStr = [];
        $params = [];

        foreach ($where as $item) {
            $whereStr[] = $item['filter'];
            $params = array_merge($params, $item['params']);
        }

        if (empty($whereStr)) {
            return null;
        }

        return [ implode(' AND ', $whereStr), $params ];
    }

    /**
     * Add a WHERE field IS NULL condition
     *
     * @param string $field The field to check for NULL
     * @return $this
     */
    public function whereIsNull(string $field): static
    {
        $this->where[] = ['filter' => "$field IS NULL", 'params' => []];
        return $this;
    }

    /**
     * Add a WHERE field IS NOT NULL condition
     *
     * @param string $field The field to check for NOT NULL
     * @return $this
     */
    public function whereIsNotNull(string $field): static
    {
        $this->where[] = ['filter' => "$field IS NOT NULL", 'params' => []];
        return $this;
    }

    /**
     * Add a WHERE field IN (values) condition
     *
     * @param string $field The field to check
     * @param array $values The values to check against
     * @return $this
     */
    public function whereIn(string $field, array $values): static
    {
        if (empty($values)) {
            return $this;
        }

        // Generate a unique prefix for this whereIn call
        $uniquePrefix = 'in_' . $field . '_' . (++self::$whereInCounter) . '_';
        
        $placeholders = [];
        $params = [];

        foreach ($values as $index => $value) {
            $placeholder = $uniquePrefix . $index;
            $placeholders[] = ":$placeholder";
            $params[$placeholder] = $value;
        }

        $placeholdersStr = implode(', ', $placeholders);
        $this->where[] = ['filter' => "$field IN ($placeholdersStr)", 'params' => $params];

        return $this;
    }

    public function unsafe()
    {
        $this->unsafe = true;
    }
}