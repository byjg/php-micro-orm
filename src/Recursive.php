<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DbDriverInterface;

class Recursive
{
    protected $name;
    protected $fields = [];
    protected $where = [];
    protected $dbDriver = null;

    public static function getInstance($name)
    {
        return new self($name);
    }

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getTableName()
    {
        return $this->name;
    }

    /**
     * Example:
     *   $recursive->field('id', 1, 'id + 10');
     */
    public function field($name, $base, $recursion)
    {
        $this->fields[$name] = ["$base as $name", $recursion];
        return $this;
    }

    /**
     * Example:
     *    $recursive->filter('price > [[amount]]', [ 'amount' => 1000] );
     *
     * @param string $filter
     * @param array $params
     * @return $this
     */
    public function where($filter)
    {
        $this->where[] = [ 'filter' => $filter ];
        return $this;
    }

    public function build(DbDriverInterface $dbDriver = null)
    {
        $this->dbDriver = $dbDriver;

        $sql = "WITH RECURSIVE {$this->name}(" . implode(", ", array_keys($this->fields)) . ") AS (";
        $sql .= $this->getBase();
        $sql .= " UNION ALL ";
        $sql .= $this->getRecursion();
        $sql .= ") ";
        return $sql;
    }

    protected function getBase()
    {
        $sql = "SELECT " . implode(", ", array_column($this->fields, 0));
        return $sql;
    }

    protected function getRecursion()
    {
        $sql = "SELECT " . implode(", ", array_column($this->fields, 1));
        $sql .= " FROM {$this->name}";
        $sql .= ' WHERE ' . implode(' AND ', array_column($this->where, 'filter'));
    
        return $sql;
    }   
}