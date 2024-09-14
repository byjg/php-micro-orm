<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Core\IteratorFilter;
use ByJG\AnyDataset\Db\IteratorFilterSqlFormatter;

trait WhereTrait
{
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
        $whereStr = [];
        $params = [];

        foreach ($this->where as $item) {
            $whereStr[] = $item['filter'];
            $params = array_merge($params, $item['params']);
        }

        if (empty($whereStr)) {
            return null;
        }

        return [ implode(' AND ', $whereStr), $params ];
    }
}