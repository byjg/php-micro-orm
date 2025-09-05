<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Core\GenericIterator;
use ByJG\AnyDataset\Db\DbDriverInterface;

class QueryRaw implements Interface\QueryBuilderInterface
{

    protected function __construct(protected string $sql, protected array $parameters = [])
    {
    }

    public static function getInstance(string $sql, array $parameters = []): QueryRaw
    {
        return new self($sql, $parameters);
    }

    public function build(?DbDriverInterface $dbDriver = null): SqlObject
    {
        return new SqlObject($this->sql, $this->parameters);
    }

    public function buildAndGetIterator(?DbDriverInterface $dbDriver = null, ?CacheQueryResult $cache = null): GenericIterator
    {
        return $dbDriver->getIterator($this->sql, $this->parameters);
    }
}