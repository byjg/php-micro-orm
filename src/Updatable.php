<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\AnyDataset\Db\DbFunctionsInterface;

abstract class Updatable implements UpdateBuilderInterface
{
    use WhereTrait;

    protected string $table = "";
    protected array $where = [];

    /**
     * Example
     *    $query->table('product');
     *
     * @param string $table
     * @return $this
     */
    public function table(string $table): static
    {
        $this->table = $table;

        return $this;
    }

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


    public function buildAndExecute(DbDriverInterface $dbDriver, $params = [], ?DbFunctionsInterface $dbHelper = null): bool
    {
        $sqlObject = $this->build($dbHelper);
        return $dbDriver->execute($sqlObject->getSql(), array_merge($sqlObject->getParameters(), $params));
    }
}
