<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Core\IteratorFilter;
use ByJG\AnyDataset\Db\IteratorFilterSqlFormatter;

trait WhereTrait
{
    protected array $where = [];
    protected bool $unsafe = false;

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

    public function unsafe()
    {
        $this->unsafe = true;
    }
}