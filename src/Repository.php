<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\MicroOrm\Exception\OrmBeforeInvalidException;
use ByJG\MicroOrm\Exception\OrmInvalidFieldsException;
use ByJG\MicroOrm\Exception\RepositoryReadOnlyException;
use ByJG\MicroOrm\Exception\UpdateConstraintException;
use ByJG\Serializer\ObjectCopy;
use ByJG\Serializer\Serialize;
use Closure;
use InvalidArgumentException;

class Repository
{

    /**
     * @var Mapper
     */
    protected Mapper $mapper;

    /**
     * @var DbDriverInterface
     */
    protected DbDriverInterface $dbDriver;

    /**
     * @var DbDriverInterface|null
     */
    protected ?DbDriverInterface $dbDriverWrite = null;

    /**
     * @var Closure|null
     */
    protected ?Closure $beforeUpdate = null;

    /**
     * @var Closure|null
     */
    protected ?Closure $beforeInsert = null;

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

    public function addDbDriverForWrite(DbDriverInterface $dbDriver): void
    {
        $this->dbDriverWrite = $dbDriver;
    }

    public function setRepositoryReadOnly(): void
    {
        $this->dbDriverWrite = null;
    }

    /**
     * @return Mapper
     */
    public function getMapper(): Mapper
    {
        return $this->mapper;
    }

    /**
     * @return DbDriverInterface
     */
    public function getDbDriver(): DbDriverInterface
    {
        return $this->dbDriver;
    }

    /**
     * @return DbDriverInterface|null
     * @throws RepositoryReadOnlyException
     */
    public function getDbDriverWrite(): ?DbDriverInterface
    {
        if (empty($this->dbDriverWrite)) {
            throw new RepositoryReadOnlyException('Repository is ReadOnly');
        }
        return $this->dbDriverWrite;
    }

    protected function getPkFilter(array|string $pkId): array
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
        foreach ($pkList as $pk) {
            $filterList[] = $pk . " = :id$pk";
            $filterKeys["id$pk"] = array_shift($pkId);
        }

        return [implode(' and ', $filterList), $filterKeys];
    }

    /**
     * @param array|string|int $pkId
     * @return mixed|null
     * @throws Exception\InvalidArgumentException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     */
    public function get(array|string|int $pkId): mixed
    {
        [$filterList, $filterKeys] = $this->getPkFilter($pkId);
        $result = $this->getByFilter($filterList, $filterKeys);

        if (count($result) === 1) {
            return $result[0];
        }

        return null;
    }

    /**
     * @param array|string|int $pkId
     * @return bool
     * @throws Exception\InvalidArgumentException
     * @throws RepositoryReadOnlyException
     */
    public function delete(array|string|int $pkId): bool
    {
        [$filterList, $filterKeys] = $this->getPkFilter($pkId);
        $updatable = Updatable::getInstance()
            ->table($this->mapper->getTable())
            ->where($filterList, $filterKeys);

        return $this->deleteByQuery($updatable);
    }

    /**
     * @param Updatable $updatable
     * @return bool
     * @throws Exception\InvalidArgumentException
     * @throws RepositoryReadOnlyException
     */
    public function deleteByQuery(Updatable $updatable): bool
    {
        $params = [];
        $sql = $updatable->buildDelete($params);

        $this->getDbDriverWrite()->execute($sql, $params);

        ORMSubject::getInstance()->notify($this->mapper->getTable(), ORMSubject::EVENT_DELETE, null, $params);

        return true;
    }

    /**
     * @param string $filter
     * @param array $params
     * @param bool $forUpdate
     * @return array
     * @throws Exception\InvalidArgumentException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     */
    public function getByFilter(string $filter, array $params, bool $forUpdate = false): array
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
     * @param array|string $arrValues
     * @param string $field
     * @return array
     * @throws Exception\InvalidArgumentException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     */
    public function filterIn(array|string $arrValues, string $field = ""): array
    {
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
     * @param QueryBuilderInterface $query
     * @return mixed
     */
    public function getScalar(QueryBuilderInterface $query): mixed
    {
        $sqlBuild = $query->build($this->getDbDriver());

        $params = $sqlBuild->getParameters();
        $sql = $sqlBuild->getSql();
        return $this->getDbDriver()->getScalar($sql, $params);
    }

    /**
     * @param QueryBuilderInterface $query
     * @param Mapper[] $mapper
     * @return array
     * @throws Exception\InvalidArgumentException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     */
    public function getByQuery(QueryBuilderInterface $query, array $mapper = []): array
    {
        $mapper = array_merge([$this->mapper], $mapper);
        $sqlBuild = $query->build($this->getDbDriver());

        $params = $sqlBuild->getParameters();
        $sql = $sqlBuild->getSql();
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
    public function getByQueryRaw(QueryBuilderInterface $query): array
    {
        $sqlObject = $query->build($this->getDbDriver());
        $iterator = $this->getDbDriver()->getIterator($sqlObject->getSql(), $sqlObject->getParameters());
        return $iterator->toArray();
    }

    /**
     * @param mixed $instance
     * @param UpdateConstraint|null $updateConstraint
     * @return mixed
     * @throws Exception\InvalidArgumentException
     * @throws OrmBeforeInvalidException
     * @throws OrmInvalidFieldsException
     * @throws RepositoryReadOnlyException
     * @throws UpdateConstraintException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     */
    public function save(mixed $instance, UpdateConstraint $updateConstraint = null): mixed
    {
        // Get all fields
        $array = Serialize::from($instance)
            ->withStopAtFirstLevel()
            ->toArray();
        $array = $this->getMapper()->prepareField($array);

        // Mapping the data
        foreach ((array)$this->getMapper()->getFieldMap() as $property => $fieldMap) {
            $fieldName = $fieldMap->getFieldName();

            // Get the value from the mapped field name
            $value = $array[$property];
            unset($array[$property]);
            $updateValue = $fieldMap->getUpdateFunctionValue($value, $instance);

            // If no value for UpdateFunction, remove from the list;
            if ($updateValue === false) {
                continue;
            }
            $array[$fieldName] = $updateValue;
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

        // Prepare query to insert
        $updatable = Updatable::getInstance()
            ->table($this->mapper->getTable())
            ->fields(array_keys($array));

        // Execute Before Statements
        if ($isInsert) {
            $closure = $this->beforeInsert;
        } else {
            $closure = $this->beforeUpdate;
        }
        $array = $closure($array);

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

        ObjectCopy::copy($array, $instance);

        ORMSubject::getInstance()->notify(
            $this->mapper->getTable(),
            $isInsert ? ORMSubject::EVENT_INSERT : ORMSubject::EVENT_UPDATE,
            $instance, $oldInstance
        );

        return $instance;
    }


    public function addObserver(ObserverProcessorInterface $observerProcessor): void
    {
        ORMSubject::getInstance()->addObserver($observerProcessor, $this);
    }

    /**
     * @param $instance
     * @param Updatable $updatable
     * @param array $params
     * @return int
     * @throws OrmInvalidFieldsException
     * @throws RepositoryReadOnlyException
     */
    protected function insert($instance, Updatable $updatable, array $params): mixed
    {
        $keyGen = $this->getMapper()->generateKey($instance);
        if (empty($keyGen)) {
            return $this->insertWithAutoInc($updatable, $params);
        } else {
            return $this->insertWithKeyGen($updatable, $params, $keyGen);
        }
    }

    /**
     * @param Updatable $updatable
     * @param array $params
     * @return int
     * @throws OrmInvalidFieldsException|RepositoryReadOnlyException
     */
    protected function insertWithAutoInc(Updatable $updatable, array $params): int
    {
        $sql = $updatable->buildInsert($params, $this->getDbDriverWrite()->getDbHelper());
        $dbFunctions = $this->getDbDriverWrite()->getDbHelper();
        return $dbFunctions->executeAndGetInsertedId($this->getDbDriverWrite(), $sql, $params);
    }

    /**
     * @param Updatable $updatable
     * @param array $params
     * @param mixed $keyGen
     * @return mixed
     * @throws OrmInvalidFieldsException
     * @throws RepositoryReadOnlyException
     */
    protected function insertWithKeyGen(Updatable $updatable, array $params, mixed $keyGen): mixed
    {
        $params[$this->mapper->getPrimaryKey()[0]] = $keyGen;
        $sql = $updatable->buildInsert($params, $this->getDbDriverWrite()->getDbHelper());
        $this->getDbDriverWrite()->execute($sql, $params);
        return $keyGen;
    }

    /**
     * @param Updatable $updatable
     * @param array $params
     * @throws Exception\InvalidArgumentException
     * @throws RepositoryReadOnlyException
     */
    protected function update(Updatable $updatable, array $params): void
    {
        $fields = array_map(function ($item) use ($params) {
            return $params[$item];
        }, $this->mapper->getPrimaryKey());

        [$filterList, $filterKeys] = $this->getPkFilter($fields);
        $updatable->where($filterList, $filterKeys);

        $sql = $updatable->buildUpdate($params, $this->getDbDriverWrite()->getDbHelper());

        $this->getDbDriverWrite()->execute($sql, $params);
    }

    public function setBeforeUpdate(Closure $closure): void
    {
        $this->beforeUpdate = $closure;
    }

    public function setBeforeInsert(Closure $closure): void
    {
        $this->beforeInsert = $closure;
    }
}
