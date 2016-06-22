<?php
/**
 * Created by PhpStorm.
 * User: jg
 * Date: 21/06/16
 * Time: 16:17
 */

namespace ByJG\MicroOrm;


use ByJG\AnyDataset\ConnectionManagement;
use ByJG\AnyDataset\Repository\DBDataset;
use ByJG\Serializer\BinderObject;

class Repository
{

    /**
     * @var ConnectionManagement
     */
    protected $connection;

    /**
     * @var Mapper
     */
    protected $mapper;

    /**
     * @var DBDataset
     */
    protected $dbDataset = null;

    /**
     * Repository constructor.
     * @param ConnectionManagement $connection
     * @param Mapper $mapper
     */
    public function __construct(ConnectionManagement $connection, Mapper $mapper)
    {
        $this->connection = $connection;
        $this->mapper = $mapper;
    }

    /**
     * @return Mapper
     */
    public function getMapper()
    {
        return $this->mapper;
    }

    /**
     * @return DBDataset
     */
    protected function getDbDataset()
    {
        if (is_null($this->dbDataset)) {
            $this->dbDataset = new DBDataset($this->connection->getDbConnectionString());
        }
        return $this->dbDataset;
    }

    /**
     * @param array|string $id
     * @return mixed|null
     */
    public function get($id)
    {
        $result = $this->getByFilter($this->mapper->getPrimaryKey() . ' = [[id]]', ['id' => $id]);

        if (count($result) === 1) {
            return $result[0];
        }

        return null;
    }

    /**
     * @param string $filter
     * @param array $params
     * @return array
     */
    public function getByFilter($filter, array $params)
    {
        $query = new Query();
        $query->table($this->mapper->getTable())
            ->where($filter, $params);

        return $this->getByQuery($query);
    }

    /**
     * @param Query $query
     * @param Mapper[] $mapper
     * @return array
     */
    public function getByQuery(Query $query, array $mapper = [])
    {
        $mapper = array_merge([$this->mapper], $mapper);
        $query = $query->getSelect();

        $result = [];
        $iterator = $this->getDbDataset()->getIterator($query['sql'], $query['params']);

        foreach ($iterator as $row) {
            $collection = [];
            foreach ($mapper as $item) {
                $instance = $item->getEntity();
                BinderObject::bindObject($row->toArray(), $instance);
                $collection[] = $instance;
            }
            $result[] = count($collection) === 1 ? $collection[0] : $collection;
        }

        return $result;
    }

    /**
     * @param mixed $instance
     */
    public function save($instance)
    {
        $array = BinderObject::toArrayFrom($instance, true);

        $query = new Query();
        $query->table($this->mapper->getTable())
            ->fields(array_keys($array));

        if (empty($array[$this->mapper->getPrimaryKey()]) || count($this->get($array[$this->mapper->getPrimaryKey()])) === 0)  {
            $array[$this->mapper->getPrimaryKey()] = $this->insert($query, $array);
            BinderObject::bindObject($array, $instance);
        } else {
            $this->update($query, $array);
        }

    }

    /**
     * @param Query $query
     * @param array $params
     * @return int
     * @throws \Exception
     */
    protected function insert(Query $query, array $params)
    {
        $sql = $query->getInsert();
        $dbFunctions = $this->getDbDataset()->getDbFunctions();
        return $dbFunctions->executeAndGetInsertedId($this->getDbDataset(), $sql, $params);
    }

    /**
     * @param Query $query
     * @param array $params
     * @throws \Exception
     */
    protected function update(Query $query, array $params)
    {
        $params = array_merge($params, ['_id' => $params[$this->mapper->getPrimaryKey()]]);
        $query->where($this->mapper->getPrimaryKey() . ' = [[_id]] ', ['_id' => $params['_id']]);
        $update = $query->getUpdate();
        $sql = $update['sql'];
        
        $this->getDbDataset()->execSQL($sql, $params);
    }
}
