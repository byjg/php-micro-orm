<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\MicroOrm\Exception\OrmBeforeInvalidException;
use ByJG\MicroOrm\Exception\OrmInvalidFieldsException;
use ByJG\MicroOrm\Exception\OrmModelInvalidException;
use ByJG\MicroOrm\Exception\RepositoryReadOnlyException;
use ByJG\MicroOrm\Exception\UpdateConstraintException;
use ByJG\Serializer\ObjectCopy;
use ByJG\Serializer\Serialize;
use Closure;
use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ReflectionException;

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
     * @param string|Mapper $mapperOrEntity
     * @throws OrmModelInvalidException
     * @throws ReflectionException
     */
    public function __construct(DbDriverInterface $dbDataset, string|Mapper $mapperOrEntity)
    {
        $this->dbDriver = $dbDataset;
        $this->dbDriverWrite = $dbDataset;
        if (is_string($mapperOrEntity)) {
            $mapperOrEntity = new Mapper($mapperOrEntity);
        }
        $this->mapper = $mapperOrEntity;
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

    public function entity(array $values): mixed
    {
        return $this->getMapper()->getEntity($values);
    }

    public function queryInstance(object $model = null): Query
    {
        $query = Query::getInstance()
            ->table($this->mapper->getTable())
        ;

        if (!is_null($model)) {
            $entity = $this->getMapper()->getEntity();
            if (!($model instanceof $entity)) {
                throw new InvalidArgumentException("The model must be an instance of " . $this->getMapper()->getEntity()::class);
            }

            $array = Serialize::from($model)
                ->withDoNotParseNullValues()
                ->toArray();

            foreach ($array as $key => $value) {
                $fieldMap = $this->mapper->getFieldMap($key);
                if (!empty($fieldMap)) {
                    $key = $fieldMap->getFieldName();
                }
                $query->where("$key = :$key", [$key => $value]);
            }
        }

        return $query;
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

    /**
     * @param array|string|int $pkId
     * @return mixed|null
     * @throws Exception\InvalidArgumentException
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     */
    public function get(array|string|int $pkId): mixed
    {
        [$filterList, $filterKeys] = $this->mapper->getPkFilter($pkId);
        $result = $this->getByFilter($filterList, $filterKeys);

        if (count($result) === 1) {
            return $result[0];
        }

        return null;
    }

    /**
     * @param array|string|int $pkId
     * @return bool
     * @throws RepositoryReadOnlyException
     */
    public function delete(array|string|int $pkId): bool
    {
        [$filterList, $filterKeys] = $this->mapper->getPkFilter($pkId);
        $updatable = DeleteQuery::getInstance()
            ->table($this->mapper->getTable())
            ->where($filterList, $filterKeys);

        return $this->deleteByQuery($updatable);
    }

    /**
     * @param UpdateBuilderInterface $updatable
     * @return bool
     * @throws RepositoryReadOnlyException
     */
    public function deleteByQuery(DeleteQuery $updatable)
    {
        $sqlObject = $updatable->build();

        $this->getDbDriverWrite()->execute($sqlObject->getSql(), $sqlObject->getParameters());

        ORMSubject::getInstance()->notify($this->mapper->getTable(), ORMSubject::EVENT_DELETE, null, $sqlObject->getParameters());

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
        $query = $this->getMapper()->getQuery()
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

        // Execute Before Statements
        if ($isInsert) {
            $closure = $this->beforeInsert;
            $array = $closure($array);
            $updatable = InsertQuery::getInstance($this->mapper->getTable(), $array);
        } else {
            $closure = $this->beforeUpdate;
            $array = $closure($array);
            $updatable = UpdateQuery::getInstance($array, $this->mapper);
        }

        // Check if is OK
        if (empty($array) || !is_array($array)) {
            throw new OrmBeforeInvalidException('Invalid Before Insert Closure');
        }

        // Execute the Insert or Update
        if ($isInsert) {
            $keyReturned = $this->insert($instance, $updatable);
            if (count($pkList) == 1) {
                $array[$pkList[0]] = $keyReturned;
            }
        } else {
            if (!empty($updateConstraint)) {
                $updateConstraint->check($oldInstance, $this->getMapper()->getEntity($array));
            }
            $this->update($updatable);
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
     * @param UpdateBuilderInterface $updatable
     * @param array $params
     * @return int
     * @throws RepositoryReadOnlyException
     */
    protected function insert($instance, InsertQuery $updatable): mixed
    {
        $keyGen = $this->getMapper()->generateKey($instance);
        if (empty($keyGen)) {
            return $this->insertWithAutoInc($updatable);
        } else {
            return $this->insertWithKeyGen($updatable, $keyGen);
        }
    }

    /**
     * @param UpdateBuilderInterface $updatable
     * @param array $params
     * @return int
     * @throws RepositoryReadOnlyException
     */
    protected function insertWithAutoinc(InsertQuery $updatable): int
    {
        $sqlObject = $updatable->build($this->getDbDriverWrite()->getDbHelper());
        $dbFunctions = $this->getDbDriverWrite()->getDbHelper();
        return $dbFunctions->executeAndGetInsertedId($this->getDbDriverWrite(), $sqlObject->getSql(), $sqlObject->getParameters());
    }

    /**
     * @param UpdateBuilderInterface $updatable
     * @param array $params
     * @param mixed $keyGen
     * @return mixed
     * @throws RepositoryReadOnlyException
     */
    protected function insertWithKeyGen(InsertQuery $updatable, mixed $keyGen): mixed
    {
        $updatable->field($this->mapper->getPrimaryKey()[0], $keyGen);
        $sqlObject = $updatable->build($this->getDbDriverWrite()->getDbHelper());
        $this->getDbDriverWrite()->execute($sqlObject->getSql(), $sqlObject->getParameters());
        return $keyGen;
    }

    /**
     * @param UpdateBuilderInterface $updatable
     * @param array $params
     * @throws RepositoryReadOnlyException
     */
    protected function update(UpdateQuery $updatable): void
    {
        $sqlObject = $updatable->build($this->getDbDriverWrite()->getDbHelper());

        $this->getDbDriverWrite()->execute($sqlObject->getSql(), $sqlObject->getParameters());
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
