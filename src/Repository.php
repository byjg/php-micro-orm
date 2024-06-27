<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\MicroOrm\Exception\OrmBeforeInvalidException;
use ByJG\MicroOrm\Exception\OrmInvalidFieldsException;
use ByJG\MicroOrm\Exception\RepositoryReadOnlyException;
use ByJG\Serializer\BinderObject;
use ByJG\Serializer\SerializerObject;
use InvalidArgumentException;

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
     * @var DbDriverInterface
     */
    protected $dbDriverWrite = null;

    /**
     * @var \Closure
     */
    protected $beforeUpdate = null;

    /**
     * @var \Closure
     */
    protected $beforeInsert = null;

    /**
     * Repository constructor.
     * @param DbDriverInterface $dbDataset
     * @param Mapper $mapper
     */
    public function __construct(DbDriverInterface $dbDataset, Mapper $mapper)
    {
        $this->dbDriver = $dbDataset;
        $this->dbDriverWrite = $dbDataset;
        $this->mapper = $mapper;
        $this->beforeInsert = function ($instance) {
            return $instance;
        };
        $this->beforeUpdate = function ($instance) {
            return $instance;
        };
    }

    public function addDbDriverForWrite(DbDriverInterface $dbDriver)
    {
        $this->dbDriverWrite = $dbDriver;
    }

    public function setRepositoryReadOnly()
    {
        $this->dbDriverWrite = null;
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
    public function getDbDriver()
    {
        return $this->dbDriver;
    }

    /**
     * @return DbDriverInterface
     */
    public function getDbDriverWrite()
    {
        if (empty($this->dbDriverWrite)) {
            throw new RepositoryReadOnlyException('Repository is ReadOnly');
        }
        return $this->dbDriverWrite;
    }

    protected function getPkFilter($pkId)
    {
        $pkList = $this->mapper->getPrimaryKey();
        if (!is_array($pkId)) {
            $pkId = [$pkId];
        }

        if (count($pkList) !== count($pkId)) {
            throw new InvalidArgumentException("The primary key must have " . count($pkList) . " values");
        }

        $filterList = [];
        $filterKeys = [];
        foreach ((array)$pkList as $pk) {
            $filterList[] = $pk . " = :id$pk";
            $filterKeys["id$pk"] = array_shift($pkId);
        }

        return [implode(' and ', $filterList), $filterKeys];
    }

    /**
     * @param array|string $pkId
     * @return mixed|null
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     */
    public function get($pkId)
    {
        [$filterList, $filterKeys] = $this->getPkFilter($pkId);
        $result = $this->getByFilter($filterList, $filterKeys);

        if (count($result) === 1) {
            return $result[0];
        }

        return null;
    }

    /**
     * @param array|int $pkId
     * @return bool
     * @throws RepositoryReadOnlyException
     * @throws Exception\InvalidArgumentException
     */
    public function delete($pkId)
    {
        [$filterList, $filterKeys] = $this->getPkFilter($pkId);
        $updatable = $this->getMapper()->getDeleteQuery()
            ->where($filterList, $filterKeys);

        return $this->deleteByQuery($updatable);
    }

    /**
     * @param DeleteQuery $updatable
     * @return bool
     * @throws Exception\InvalidArgumentException
     * @throws RepositoryReadOnlyException
     */
    public function deleteByQuery(DeleteQuery $updatable)
    {
        $params = [];
        $sql = $updatable->build($params);

        $this->getDbDriverWrite()->execute($sql, $params);

        ORMSubject::getInstance()->notify($this->mapper->getTable(), ORMSubject::EVENT_DELETE, null, $params);

        return true;
    }

    /**
     * @param string $filter
     * @param array $params
     * @param bool $forUpdate
     * @return array
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     */
    public function getByFilter($filter, array $params, $forUpdate = false)
    {
        $query = $this->getMapper()->getQuery()
            ->where($filter, $params);

        if ($forUpdate) {
            $query->forUpdate();
        }

        return $this->getByQuery($query);
    }

    /**
     * @param array $arrValues
     * @param $field
     * @return array
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     */
    public function filterIn($arrValues, $field = "") {
        $arrValues = (array) $arrValues;

        if (empty($field)) {
            $field = $this->getMapper()->getPrimaryKey()[0];
        }

        return $this->getByFilter(
            $field . " in (:" . implode(',:', array_keys($arrValues)) . ')',
            $arrValues
        );
    }

    /**
     * @param \ByJG\MicroOrm\QueryBuilderInterface $query
     * @return mixed
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     */
    public function getScalar(QueryBuilderInterface $query)
    {
        $query = $query->build($this->getDbDriver());

        $params = $query['params'];
        $sql = $query['sql'];
        return $this->getDbDriver()->getScalar($sql, $params);
    }

    /**
     * @param QueryBuilderInterface $query
     * @param Mapper[] $mapper
     * @return array
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     */
    public function getByQuery(QueryBuilderInterface $query, array $mapper = [])
    {
        $mapper = array_merge([$this->mapper], $mapper);
        $query = $query->build($this->getDbDriver());

        $params = $query['params'];
        $sql = $query['sql'];
        $result = [];
        $iterator = $this->getDbDriver()->getIterator($sql, $params);

        foreach ($iterator as $row) {
            $collection = [];
            foreach ($mapper as $item) {
                $instance = $item->getEntity($row->toArray());
                $collection[] = $instance;
            }
            $result[] = count($collection) === 1 ? $collection[0] : $collection;
        }

        return $result;
    }

    /**
     * Get by Query without map to an instance.
     *
     * @param Query $query
     * @return array
     * @throws Exception\InvalidArgumentException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     */
    public function getByQueryRaw(QueryBuilderInterface $query)
    {
        $query = $query->build($this->getDbDriver());
        $iterator = $this->getDbDriver()->getIterator($query['sql'], $query['params']);
        return $iterator->toArray();
    }

    /**
     * @param mixed $instance
     * @return mixed
     * @throws \ByJG\MicroOrm\Exception\InvalidArgumentException
     * @throws \ByJG\MicroOrm\Exception\OrmBeforeInvalidException
     * @throws \ByJG\MicroOrm\Exception\OrmInvalidFieldsException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     */
    public function save($instance, UpdateConstraint $updateConstraint = null)
    {
        // Get all fields
        $array = SerializerObject::instance($instance)
            ->withStopAtFirstLevel()
            ->serialize();
        $array = $this->getMapper()->prepareField($array);

        // Mapping the data
        foreach ((array)$this->getMapper()->getFieldMap() as $property => $fieldMap) {
            $fieldname = $fieldMap->getFieldName();

            // Get the value from the mapped field name
            $value = $array[$property];
            unset($array[$property]);
            $updateValue = $fieldMap->getUpdateFunctionValue($value, $instance);

            // If no value for UpdateFunction, remove from the list;
            if ($updateValue === false) {
                continue;
            }
            $array[$fieldname] = $updateValue;
        }

        // Defines if is Insert or Update
        $pkList = $this->getMapper()->getPrimaryKey();
        $oldInstance = null;
        if (count($pkList) == 1) {
            $pk = $pkList[0];
            if (!empty($array[$pk])) {
                $oldInstance = $this->get($array[$pk]);
            }
        } else {
            $fields = array_map(function ($item) use ($array) {
                return $array[$item];
            }, $pkList);
            $oldInstance = $this->get($fields);
        }
        $isInsert = empty($oldInstance);

        // Execute Before Statements
        if ($isInsert) {
            $closure = $this->beforeInsert;
            $array = $closure($array);
            $updatable = $this->getMapper()->getInsertQuery()
                ->fields(array_keys($array));
        } else {
            $closure = $this->beforeUpdate;
            $array = $closure($array);
            $updatable = $this->getMapper()->getUpdateQuery();
            foreach ($array as $field => $value) {
                $updatable->set($field, $value);
            }
        }

        // Check if is OK
        if (empty($array) || !is_array($array)) {
            throw new OrmBeforeInvalidException('Invalid Before Insert Closure');
        }

        // Execute the Insert or Update
        if ($isInsert) {
            $keyReturned = $this->insert($instance, $updatable, $array);
            if (count($pkList) == 1) {
                $array[$pkList[0]] = $keyReturned;
            }
        } else {
            if (!empty($updateConstraint)) {
                $updateConstraint->check($oldInstance, $this->getMapper()->getEntity($array));
            }
            $this->update($updatable, $array);
        }

        BinderObject::bind($array, $instance);

        ORMSubject::getInstance()->notify(
            $this->mapper->getTable(),
            $isInsert ? ORMSubject::EVENT_INSERT : ORMSubject::EVENT_UPDATE,
            $instance, $oldInstance
        );

        return $instance;
    }


    public function addObserver(ObserverProcessorInterface $observerProcessor)
    {
        ORMSubject::getInstance()->addObserver($observerProcessor, $this);
    }

    /**
     * @param $instance
     * @param UpdateBuilderInterface $updatable
     * @param array $params
     * @return int
     * @throws OrmInvalidFieldsException
     */
    protected function insert($instance, InsertQuery $updatable, array $params)
    {
        $keyGen = $this->getMapper()->generateKey($instance);
        if (empty($keyGen)) {
            return $this->insertWithAutoinc($updatable, $params);
        } else {
            return $this->insertWithKeyGen($updatable, $params, $keyGen);
        }
    }

    /**
     * @param UpdateBuilderInterface $updatable
     * @param array $params
     * @return int
     * @throws RepositoryReadOnlyException
     */
    protected function insertWithAutoinc(InsertQuery $updatable, array $params)
    {
        $sql = $updatable->build($params, $this->getDbDriverWrite()->getDbHelper());
        $dbFunctions = $this->getDbDriverWrite()->getDbHelper();
        return $dbFunctions->executeAndGetInsertedId($this->getDbDriverWrite(), $sql, $params);
    }

    /**
     * @param UpdateBuilderInterface $updatable
     * @param array $params
     * @param $keyGen
     * @return mixed
     * @throws RepositoryReadOnlyException
     */
    protected function insertWithKeyGen(InsertQuery $updatable, array $params, $keyGen)
    {
        $params[$this->mapper->getPrimaryKey()[0]] = $keyGen;
        $sql = $updatable->build($params, $this->getDbDriverWrite()->getDbHelper());
        $this->getDbDriverWrite()->execute($sql, $params);
        return $keyGen;
    }

    /**
     * @param UpdateBuilderInterface $updatable
     * @param array $params
     * @throws RepositoryReadOnlyException
     */
    protected function update(UpdateQuery $updatable, array $params)
    {
        $fields = array_map(function ($item) use ($params) {
            return $params[$item];
        }, $this->mapper->getPrimaryKey());

        [$filterList, $filterKeys] = $this->getPkFilter($fields);
        $updatable->where($filterList, $filterKeys);

        $sql = $updatable->build($params, $this->getDbDriverWrite()->getDbHelper());

        $this->getDbDriverWrite()->execute($sql, $params);
    }

    public function setBeforeUpdate(\Closure $closure)
    {
        $this->beforeUpdate = $closure;
    }

    public function setBeforeInsert(\Closure $closure)
    {
        $this->beforeInsert = $closure;
    }
}
