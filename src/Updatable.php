<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\AnyDataset\Db\DbFunctionsInterface;
use ByJG\MicroOrm\Interface\UpdateBuilderInterface;
use Override;

abstract class Updatable implements UpdateBuilderInterface
{
    use WhereTrait;

    protected string $table = "";

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

    #[Override]
    public function buildAndExecute(DbDriverInterface $dbDriver, $params = [], ?DbFunctionsInterface $dbHelper = null): bool
    {
        $sqlStatement = $this->build($dbHelper)->withParams($params);
        return $dbDriver->execute($sqlStatement);
    }
}
