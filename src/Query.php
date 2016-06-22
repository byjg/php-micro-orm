<?php
/**
 * Created by PhpStorm.
 * User: jg
 * Date: 21/06/16
 * Time: 12:01
 */

namespace ByJG\MicroOrm;


class Query
{
    protected $fields = [];
    protected $table = "";
    protected $where = [];
    protected $groupBy = [];
    protected $orderBy = [];
    protected $join = [];
    
    protected $limitStart = null;
    protected $limitEnd = null;
    protected $top = null;

    /**
     * Example:
     *   $query->fields(['name', 'price']);
     * 
     * @param array $fields
     * @return $this
     */
    public function fields(array $fields)
    {
        $this->fields = array_merge($this->fields, $fields);
        
        return $this;
    }

    /**
     * Example
     *    $query->table('product');
     * 
     * @param string $table
     * @return $this
     */
    public function table($table) 
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Example:
     *    $query->join('sales', 'product.id = sales.id');
     * 
     * @param string $table
     * @param string $filter
     * @return $this
     */
    public function join($table, $filter)
    {
        $this->join[] = [ 'table'=>$table, 'filter'=>$filter];
        return $this;
    }

    /**
     * Example:
     *    $query->filter('price > [[amount]]', [ 'amount' => 1000] );
     * 
     * @param string $filter
     * @param array $params
     * @return $this
     */
    public function where($filter, array $params = [])
    {
        $this->where[] = [ 'filter' => $filter, 'params' => $params  ];
        return $this;
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

    public function limit($start, $end)
    {

        return $this;
    }

    public function top($top)
    {

        return $this;
    }
    
    protected function getFields()
    {
        if (empty($this->fields)) {
            return ' * ';
        }

        return ' ' . implode(', ', $this->fields) . ' ';
    }
    
    protected function getJoin()
    {
        $join = $this->table;
        foreach ($this->join as $item) {
            $join .= ' INNER JOIN ' . $item['table'] . ' ON ' . $item['filter'];
        }
        return $join;
    }
    
    protected function getWhere() 
    {
        $where = [];
        $params = [];

        foreach ($this->where as $item) {
            $where[] = $item['filter'];
            $params = array_merge($params, $item['params']);
        }
        
        if (empty($where)) {
            return null;
        }
        
        return [ implode(' AND ', $where), $params ];
    }

    /**
     * @return array
     */
    public function getSelect()
    {
        $sql = "SELECT " .
            $this->getFields() . 
            "FROM " . $this->getJoin();
        
        $where = $this->getWhere();
        $params = null;
        if (!is_null($where)) {
            $sql .= ' WHERE ' . $where[0];
            $params = $where[1];
        }
        
        if (!empty($this->groupBy)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groupBy);
        }

        if (!empty($this->orderBy)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBy);
        }
        
        return [ 'sql' => $sql, 'params' => $params ];
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getInsert()
    {
        if (empty($this->fields)) {
            throw new \Exception('You must specifiy the fields for insert');
        }
        
        $sql = 'INSERT INTO '
            . $this->table
            . '( ' . implode(', ', $this->fields) . ' ) '
            . ' values '
            . '( [[' . implode(']], [[', $this->fields) . ']] ) ';
        
        return $sql;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getUpdate()
    {
        if (empty($this->fields)) {
            throw new \Exception('You must specifiy the fields for insert');
        }
        
        $fields = [];
        foreach ($this->fields as $field) {
            $fields[] = "$field = [[$field]] ";
        }
        
        $where = $this->getWhere();
        if (is_null($where)) {
            throw new \Exception('You must specifiy a where clause');
        }

        $sql = 'UPDATE ' . $this->table . ' SET '
            . implode(', ', $fields)
            . ' WHERE ' . $where[0];

        return [ 'sql' => $sql, 'params' => $where[1] ];
    }


}
