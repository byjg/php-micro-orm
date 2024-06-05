<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\AnyDataset\Db\Factory;
use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\Util\Uri;

class Union implements QueryBuilderInterface
{
    protected $queryList = [];

    protected $queryAgreggation = null;

    public function __construct()
    {
        $this->queryAgreggation = Query::getInstance()->table("__TMP__");
    }

    public static function getInstance(): Union
    {
        return new Union();
    }

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
        $this->queryAgreggation->orderBy($fields);

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
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     */
    public function limit($start, $end): Union
    {
        $this->queryAgreggation->limit($start, $end);
        return $this;
    }

    /**
     * @param $top
     * @return $this
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     */
    public function top($top): Union
    {
        $this->queryAgreggation->top($top);
        return $this;
    }

    public function build(?DbDriverInterface $dbDriver  = null)
    {
        $unionQuery = [];
        $params = [];
        foreach ($this->queryList as $query) {
            $build = $query->build($dbDriver);
            $unionQuery[] = $build['sql'];
            $params = array_merge($params, $build['params']);
        }

        $unionQuery = implode(" UNION ", $unionQuery);

        $build = $this->queryAgreggation->build($dbDriver);

        $unionQuery = trim($unionQuery . " " . substr($build['sql'], strpos($build['sql'], "__TMP__") + 8));

        return ["sql" => $unionQuery, "params" => $params];
    }


}