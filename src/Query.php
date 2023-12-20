<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\Serializer\SerializerObject;

class Query extends QueryBasic
{
    protected $groupBy = [];
    protected $orderBy = [];
    protected $limitStart = null;
    protected $limitEnd = null;
    protected $top = null;
    protected $forUpdate = false;

    public static function getInstance(): Query
    {
        return new Query();
    }

    /**
     * Example:
     *    $query->groupBy(['name']);
     *
     * @param array $fields
     * @return $this
     */
    public function groupBy(array $fields)
    {
        $this->groupBy = array_merge($this->groupBy, $fields);
    
        return $this;
    }

    /**
     * Example:
     *     $query->orderBy(['price desc']);
     *
     * @param array $fields
     * @return $this
     */
    public function orderBy(array $fields)
    {
        $this->orderBy = array_merge($this->orderBy, $fields);

        return $this;
    }

    public function forUpdate()
    {
        $this->forUpdate = true;
        
        return $this;
    }

    /**
     * @param $start
     * @param $end
     * @return $this
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     */
    public function limit($start, $end)
    {
        if (!is_null($this->top)) {
            throw new InvalidArgumentException('You cannot mix TOP and LIMIT');
        }
        $this->limitStart = $start;
        $this->limitEnd = $end;
        return $this;
    }

    /**
     * @param $top
     * @return $this
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     */
    public function top($top)
    {
        if (!is_null($this->limitStart)) {
            throw new InvalidArgumentException('You cannot mix TOP and LIMIT');
        }
        $this->top = $top;
        return $this;
    }


    /**
     * @param \ByJG\AnyDataset\Db\DbDriverInterface|null $dbDriver
     * @return array
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     */
    public function build(?DbDriverInterface $dbDriver = null)
    {
        $buildResult = parent::build($dbDriver);
        $sql = $buildResult['sql'];
        $params = $buildResult['params'];

        $sql .= $this->addGroupBy();

        $sql .= $this->addOrderBy();

        $sql = $this->addforUpdate($dbDriver, $sql);

        $sql = $this->addTop($dbDriver, $sql);

        $sql = $this->addLimit($dbDriver, $sql);

        $sql = ORMHelper::processLiteral($sql, $params);

        return [ 'sql' => $sql, 'params' => $params ];
    }

    protected function addOrderBy()
    {
        if (empty($this->orderBy)) {
            return "";
        }
        return ' ORDER BY ' . implode(', ', $this->orderBy);
    }

    protected function addGroupBy()
    {
        if (empty($this->groupBy)) {
            return "";
        }
        return ' GROUP BY ' . implode(', ', $this->groupBy);
    }

    /**
     * @param DbDriverInterface $dbDriver
     * @param string $sql
     * @return string
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     */
    protected function addforUpdate($dbDriver, $sql)
    {
        if (empty($this->forUpdate)) {
            return $sql;
        }

        if (is_null($dbDriver)) {
            throw new InvalidArgumentException('To get FOR UPDATE working you have to pass the DbDriver');
        }

        return $dbDriver->getDbHelper()->forUpdate($sql);
    }

    /**
     * @param DbDriverInterface $dbDriver
     * @param string $sql
     * @return string
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     */
    protected function addTop($dbDriver, $sql)
    {
        if (empty($this->top)) {
            return $sql;
        }

        if (is_null($dbDriver)) {
            throw new InvalidArgumentException('To get Limit and Top working you have to pass the DbDriver');
        }

        return $dbDriver->getDbHelper()->top($sql, $this->top);
    }

    /**
     * @param DbDriverInterface $dbDriver
     * @param string $sql
     * @return string
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     */
    protected function addLimit($dbDriver, $sql)
    {
        if (empty($this->limitStart) && ($this->limitStart !== 0)) {
            return $sql;
        }

        if (is_null($dbDriver)) {
            throw new InvalidArgumentException('To get Limit and Top working you have to pass the DbDriver');
        }

        return $dbDriver->getDbHelper()->limit($sql, $this->limitStart, $this->limitEnd);
    }
}
