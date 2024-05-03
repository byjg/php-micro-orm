<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DbDriverInterface;

class Recursive
{
    protected string $name;
    protected array $fields = [];
    protected array $where = [];
    protected ?DbDriverInterface $dbDriver = null;

    public static function getInstance(string $name): Recursive
    {
        return new self($name);
    }

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getTableName(): string
    {
        return $this->name;
    }

    /**
     * Example:
     *   $recursive->field('id', 1, 'id + 10');
     */
    public function field(string $name, string $base, string $recursion): static
    {
        $this->fields[$name] = ["$base as $name", $recursion];
        return $this;
    }

    /**
     * Example:
     *    $recursive->filter('price > [[amount]]', [ 'amount' => 1000] );
     *
     * @param string $filter
     * @return $this
     */
    public function where(string $filter): static
    {
        $this->where[] = [ 'filter' => $filter ];
        return $this;
    }

    public function build(DbDriverInterface $dbDriver = null): SqlObject
    {
        $this->dbDriver = $dbDriver;

        $sql = "WITH RECURSIVE $this->name(" . implode(", ", array_keys($this->fields)) . ") AS (";
        $sql .= $this->getBase();
        $sql .= " UNION ALL ";
        $sql .= $this->getRecursion();
        $sql .= ") ";
        return new SqlObject($sql);
    }

    protected function getBase(): string
    {
        return "SELECT " . implode(", ", array_column($this->fields, 0));
    }

    protected function getRecursion(): string
    {
        $sql = "SELECT " . implode(", ", array_column($this->fields, 1));
        $sql .= " FROM $this->name";
        $sql .= ' WHERE ' . implode(' AND ', array_column($this->where, 'filter'));
    
        return $sql;
    }   
}