<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Core\GenericIterator;
use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\AnyDataset\Db\SqlStatement;
use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\MicroOrm\Interface\QueryBuilderInterface;
use Override;

class Union implements QueryBuilderInterface
{
    protected array $queryList = [];

    protected ?Query $queryAggregation = null;

    public function __construct()
    {
        $this->queryAggregation = Query::getInstance()->table("__TMP__");
    }

    public static function getInstance(): Union
    {
        return new Union();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function addQuery(QueryBasic $query): Union
    {
        if (get_class($query) !== QueryBasic::class) {
            throw new InvalidArgumentException("The query must be an instance of " . QueryBasic::class);
        }

        $this->queryList[] = $query;
        return $this;
    }

    /**
     * Example:
     *     $query->orderBy(['price desc']);
     *
     * @param array $fields
     * @return $this
     */
    public function orderBy(array $fields): Union
    {
        $this->queryAggregation->orderBy($fields);

        return $this;
    }

    public function groupBy(array $fields): Union
    {
        $this->queryAggregation->groupBy($fields);

        return $this;
    }

    /**
     * @param int $start
     * @param int $end
     * @return $this
     * @throws InvalidArgumentException
     */
    public function limit(int $start, int $end): Union
    {
        $this->queryAggregation->limit($start, $end);
        return $this;
    }

    /**
     * @param int $top
     * @return $this
     * @throws InvalidArgumentException
     */
    public function top(int $top): Union
    {
        $this->queryAggregation->top($top);
        return $this;
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Override]
    public function build(?DbDriverInterface $dbDriver = null): SqlStatement
    {
        $unionQuery = [];
        $params = [];
        foreach ($this->queryList as $query) {
            $build = $query->build($dbDriver);
            $unionQuery[] = $build->getSql();
            $params = array_merge($params, $build->getParams());
        }

        $unionQuery = implode(" UNION ", $unionQuery);

        $build = $this->queryAggregation->build($dbDriver);

        $unionQuery = trim($unionQuery . " " . substr($build->getSql(), strpos($build->getSql(), "__TMP__") + 8));

        return new SqlStatement($unionQuery, $params);
    }


    #[Override]
    public function buildAndGetIterator(?DbDriverInterface $dbDriver = null, ?CacheQueryResult $cache = null): GenericIterator
    {
        $sqlStatement = $this->build($dbDriver);
        if (!empty($cache)) {
            $sqlStatement = $sqlStatement->withCache($cache->getCache(), $cache->getCacheKey(), $cache->getTtl());
        }
        return $dbDriver->getIterator($sqlStatement);
    }
}