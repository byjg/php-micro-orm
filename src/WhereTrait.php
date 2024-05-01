<?php

namespace ByJG\MicroOrm;

trait WhereTrait
{
    /**
     * Example:
     *    $query->filter('price > [[amount]]', [ 'amount' => 1000] );
     *
     * @param string $filter
     * @param array $params
     * @return $this
     */
    public function where(string $filter, array $params = []): static
    {
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