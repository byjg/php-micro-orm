<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Core\GenericIterator;
use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\AnyDataset\Db\SqlStatement;
use ByJG\MicroOrm\Interface\QueryBuilderInterface;
use Override;

class QueryRaw implements QueryBuilderInterface
{

    protected function __construct(protected string $sql, protected array $parameters = [])
    {
    }

    public static function getInstance(string $sql, array $parameters = []): QueryRaw
    {
        return new self($sql, $parameters);
    }

    #[Override]
    public function build(?DbDriverInterface $dbDriver = null): SqlStatement
    {
        return new SqlStatement($this->sql, $this->parameters);
    }

    #[Override]
    public function buildAndGetIterator(DatabaseExecutor $executor, ?CacheQueryResult $cache = null): GenericIterator
    {
        return $executor->getIterator($this->build($executor->getDriver()));
    }
}