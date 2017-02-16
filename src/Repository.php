<?php
/**
 * Created by PhpStorm.
 * User: jg
 * Date: 21/06/16
 * Time: 16:17
 */

namespace ByJG\MicroOrm;


use ByJG\AnyDataset\DbDriverInterface;
use ByJG\Serializer\BinderObject;

class Repository
{

    /**
     * @var Mapper
     */
    protected $mapper;

    /**
     * @var DbDriverInterface
     */
    protected $dbDriver = null;

    /**
     * Repository constructor.
     * @param DbDriverInterface $dbDataset
     * @param Mapper $mapper
     */
    public function __construct(DbDriverInterface $dbDataset, Mapper $mapper)
    {
        $this->dbDriver = $dbDataset;
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
     * @return DbDriverInterface
     */
    protected function getDbDriver()
    {
        return $this->dbDriver;
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
     * @param array $id
     * @return mixed|null
     */
    public function delete($id)
    {
        $params = ['id' => $id];
        $query = new Query();
        $query->table($this->mapper->getTable())
            ->where($this->mapper->getPrimaryKey() . ' = [[id]]', $params);

        return $this->deleteByQuery($query);
    }

    /**
     * @param $query
     * @return bool
     */
    public function deleteByQuery($query)
    {
        $delete = $query->getDelete();
        $sql = $delete['sql'];
        $params = $delete['params'];

        $this->getDbDriver()->execute($sql, $params);

        return true;
    }

    /**
     * @param string $filter
     * @param array $params
     * @param bool $forUpdate
     * @return array
     */
    public function getByFilter($filter, array $params, $forUpdate = false)
    {
        $query = new Query();
        $query->table($this->mapper->getTable())
            ->where($filter, $params);
        
        if ($forUpdate) {
            $query->forUpdate();
        }

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
        $iterator = $this->getDbDriver()->getIterator($query['sql'], $query['params']);

        foreach ($iterator as $row) {
            $collection = [];
            foreach ($mapper as $item) {
                $instance = $item->getEntity();
                BinderObject::bindObject($row->toArray(), $instance);

                foreach ((array)$item->getFieldMap() as $property => $fieldName) {
                    $instance->$property = $row->getField($fieldName);
                }
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
        $dbFunctions = $this->getDbDriver()->getDbHelper();
        return $dbFunctions->executeAndGetInsertedId($this->getDbDriver(), $sql, $params);
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
        
        $this->getDbDriver()->execute($sql, $params);
    }
}
