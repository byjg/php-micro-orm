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

    protected $limitStart = null;
    protected $limitEnd = null;
    protected $top = null;

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
    public function deleteByQuery(Query $query)
    {
        $delete = $query->getDelete();
        $sql = $delete['sql'];
        $params = $delete['params'];

        if (is_array($params)) {
            $sql = $this->processLiteral($sql, $params);
        }

        $this->getDbDriver()->execute($sql, $params);

        return true;
    }

    public function limit($start, $end)
    {
        $this->limitStart = $start;
        $this->limitEnd = $end;
        $this->top = null;
        return $this;
    }

    public function top($top)
    {
        $this->top = $top;
        $this->limitStart = $this->limitEnd = null;

        return $this;
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

        if (!empty($this->top)) {
            $query['sql'] = $this->getDbDriver()->getDbHelper()->top($query['sql'], $this->top);
        }

        if (!empty($this->limitStart)) {
            $query['sql'] = $this->getDbDriver()->getDbHelper()->limit($query['sql'], $this->limitStart, $this->limitEnd);
        }

        $params = $query['params'];
        $sql = $query['sql'];
        if (is_array($params)) {
            $sql = $this->processLiteral($sql, $params);
        }
        $result = [];
        $iterator = $this->getDbDriver()->getIterator($sql, $params);

        foreach ($iterator as $row) {
            $collection = [];
            foreach ($mapper as $item) {
                $instance = $item->getEntity();
                $data = $row->toArray();

                BinderObject::bindObject($data, $instance);
                foreach ((array)$item->getFieldMap() as $property => $fieldmap) {
                    $selectMask = $fieldmap[Mapper::FIELDMAP_SELECTMASK];
                    $data[$property] = $selectMask($row->get($fieldmap[Mapper::FIELDMAP_FIELD]), $instance);
                }
                if (count($item->getFieldMap()) > 0) {
                    BinderObject::bindObject($data, $instance);
                }
                $collection[] = $instance;
            }
            $result[] = count($collection) === 1 ? $collection[0] : $collection;
        }

        $this->limitStart = $this->limitEnd = $this->top = null;

        return $result;
    }

    /**
     * @param mixed $instance
     */
    public function save($instance)
    {
        // Get all fields
        $array = BinderObject::toArrayFrom($instance, true);

        // Mapping the data
        foreach ((array)$this->getMapper()->getFieldMap() as $property => $fieldmap) {
            $fieldname = $fieldmap[Mapper::FIELDMAP_FIELD];
            $updateMask = $fieldmap[Mapper::FIELDMAP_UPDATEMASK];

            // If no value for UpdateMask, remove from the list;
            if (empty($updateMask)) {
                unset($array[$property]);
                continue;
            }

            // Get the value from the mapped field name
            $value = $array[$property];
            unset($array[$property]);
            $array[$fieldname] = $updateMask($value, $instance);
        }

        // Prepare query to insert
        $query = new Query();
        $query->table($this->mapper->getTable())
            ->fields(array_keys($array));

        // Check if is insert or update
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
        $keyGen = $this->getMapper()->generateKey();
        if (empty($keyGen)) {
            return $this->insertWithAutoinc($query, $params);
        } else {
            return $this->insertWithKeyGen($query, $params, $keyGen);
        }
    }

    protected function insertWithAutoinc(Query $query, array $params)
    {
        $sql = $this->processLiteral($query->getInsert(), $params);
        $dbFunctions = $this->getDbDriver()->getDbHelper();
        return $dbFunctions->executeAndGetInsertedId($this->getDbDriver(), $sql, $params);
    }

    protected function insertWithKeyGen(Query $query, array $params, $keyGen)
    {
        $params[$this->mapper->getPrimaryKey()] = $keyGen;
        $sql = $this->processLiteral($query->getInsert(), $params);
        $this->getDbDriver()->execute($sql, $params);
        return $keyGen;
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
        $sql = $this->processLiteral($update['sql'], $params);

        $this->getDbDriver()->execute($sql, $params);
    }

    protected function processLiteral($sql, array &$params)
    {
        foreach ($params as $field => $param) {
            if ($param instanceof Literal) {
                $sql = str_replace('[[' . $field . ']]', $param->getLiteralValue(), $sql);
                unset($params[$field]);
            }
        }

        return $sql;
    }
}
