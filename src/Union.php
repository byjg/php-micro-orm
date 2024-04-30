<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\MicroOrm\Exception\InvalidArgumentException;

class Union implements QueryBuilderInterface
{
    protected array $queryList = [];

    protected QueryBasic|null $queryAggregation = null;

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
        $this->queryAgreggation->groupBy($fields);

        return $this;
    }

    /**
     * @param $start
     * @param $end
     * @return $this
     * @throws InvalidArgumentException
     */
    public function limit($start, $end): Union
    {
        $this->queryAggregation->limit($start, $end);
        return $this;
    }

    /**
     * @param $top
     * @return $this
     * @throws InvalidArgumentException
     */
    public function top($top): Union
    {
        $this->queryAggregation->top($top);
        return $this;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function build(?DbDriverInterface $dbDriver  = null): SqlObject
    {
        $unionQuery = [];
        $params = [];
        foreach ($this->queryList as $query) {
            $build = $query->build($dbDriver);
            $unionQuery[] = $build->getSql();
            $params = array_merge($params, $build->getParameters());
        }

        $unionQuery = implode(" UNION ", $unionQuery);

        $build = $this->queryAggregation->build($dbDriver);

        $unionQuery = trim($unionQuery . " " . substr($build->getSql(), strpos($build->getSql(), "__TMP__") + 8));

        return new SqlObject($unionQuery, $params);
    }


}