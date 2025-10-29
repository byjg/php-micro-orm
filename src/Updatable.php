<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DatabaseExecutor;
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
    public function buildAndExecute(DatabaseExecutor $executor, $params = []): bool
    {
        $sqlStatement = $this->build($executor->getHelper());
        if (!empty($params)) {
            $sqlStatement = $sqlStatement->withParams($params);
        }
        return $executor->execute($sqlStatement);
    }
}
