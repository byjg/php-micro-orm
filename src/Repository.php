<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Core\Enum\Relation;
use ByJG\AnyDataset\Core\IteratorFilter;
use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\AnyDataset\Db\IteratorFilterSqlFormatter;
use ByJG\AnyDataset\Db\SqlStatement;
use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\MicroOrm\Exception\OrmBeforeInvalidException;
use ByJG\MicroOrm\Exception\OrmInvalidFieldsException;
use ByJG\MicroOrm\Exception\OrmModelInvalidException;
use ByJG\MicroOrm\Exception\RepositoryReadOnlyException;
use ByJG\MicroOrm\Exception\UpdateConstraintException;
use ByJG\MicroOrm\Interface\ObserverProcessorInterface;
use ByJG\MicroOrm\Interface\QueryBuilderInterface;
use ByJG\MicroOrm\Literal\Literal;
use ByJG\MicroOrm\Literal\LiteralInterface;
use ByJG\Serializer\ObjectCopy;
use ByJG\Serializer\Serialize;
use Closure;
use ReflectionException;
use stdClass;

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

    public function queryInstance(object $model = null, string ...$tales): Query
    {
        $query = Query::getInstance()
            ->table($this->mapper->getTable(), $this->mapper->getTableAlias())
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
     * @param array|string|int|LiteralInterface $pkId
     * @return mixed|null
     * @throws InvalidArgumentException
     */
    public function get(array|string|int|LiteralInterface $pkId): mixed
    {
        [$filterList, $filterKeys] = $this->mapper->getPkFilter($pkId);
        $result = $this->getByFilter($filterList, $filterKeys);

        if (count($result) === 1) {
            return $result[0];
        }

        return null;
    }

    /**
     * @param array|string|int|LiteralInterface $pkId
     * @return bool
     * @throws InvalidArgumentException
     * @throws RepositoryReadOnlyException
     */
    public function delete(array|string|int|LiteralInterface $pkId): bool
    {
        [$filterList, $filterKeys] = $this->mapper->getPkFilter($pkId);

        if ($this->mapper->isSoftDeleteEnabled()) {
            $updatable = UpdateQuery::getInstance()
                ->table($this->mapper->getTable())
                ->set('deleted_at', new Literal($this->getDbDriverWrite()->getDbHelper()->sqlDate('Y-m-d H:i:s')))
                ->where($filterList, $filterKeys);
            $this->update($updatable);
            return true;
        }

        $updatable = DeleteQuery::getInstance()
            ->table($this->mapper->getTable())
            ->where($filterList, $filterKeys);

        return $this->deleteByQuery($updatable);
    }

    /**
     * @param DeleteQuery $updatable
     * @return bool
     * @throws InvalidArgumentException
     * @throws RepositoryReadOnlyException
     */
    public function deleteByQuery(DeleteQuery $updatable): bool
    {
        $sqlObject = $updatable->build();

        $this->getDbDriverWrite()->execute($sqlObject->getSql(), $sqlObject->getParameters());

        ORMSubject::getInstance()->notify($this->mapper->getTable(), ORMSubject::EVENT_DELETE, null, $sqlObject->getParameters());

        return true;
    }

    /**
     * @param string|IteratorFilter $filter
     * @param array $params
     * @param bool $forUpdate
     * @return array
     */
    public function getByFilter(string|IteratorFilter $filter = "", array $params = [], bool $forUpdate = false, int $page = 0, ?int $limit = null): array
    {
        if ($filter instanceof IteratorFilter) {
            $formatter = new IteratorFilterSqlFormatter();
            $filter = $formatter->getFilter($filter->getRawFilters(), $params);
        }

        $query = $this->getMapper()->getQuery();
        if (!empty($filter)) {
            $query->where($filter, $params);
        }

        if ($forUpdate) {
            $query->forUpdate();
        }

        if (!is_null($limit)) {
            $query->limit($page, ($page + 1) * $limit);
        }

        return $this->getByQuery($query);
    }

    /**
     * @param array|string|int|LiteralInterface $arrValues
     * @param string $field
     * @return array
     */
    public function filterIn(array|string|int|LiteralInterface $arrValues, string $field = ""): array
    {
        $arrValues = (array) $arrValues;

        if (empty($field)) {
            $field = $this->getMapper()->getPrimaryKey()[0];
        }

        $iteratorFilter = new IteratorFilter();
        $iteratorFilter->addRelation($field, Relation::IN, $arrValues);

        return $this->getByFilter($iteratorFilter);
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
     */
    public function getByQuery(QueryBuilderInterface $query, array $mapper = [], ?CacheQueryResult $cache = null): array
    {
        $mapper = array_merge([$this->mapper], $mapper);
        $sqlBuild = $query->build($this->getDbDriver());

        $params = $sqlBuild->getParameters();
        $sql = new SqlStatement($sqlBuild->getSql());
        if (!empty($cache)) {
            $sql->withCache($cache->getCache(), $cache->getCacheKey(), $cache->getTtl());
        }
        $result = [];
        $iterator = $sql->getIterator($this->getDbDriver(), $params);

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
     * @throws InvalidArgumentException
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
        $fieldToProperty = [];
        $mapper = $this->getMapper();

        // Copy the values to the instance
        $valuesToUpdate = new stdClass();
        ObjectCopy::copy(
            $array,
            $valuesToUpdate,
            function ($sourcePropertyName) use ($mapper, &$fieldToProperty) {
                $sourcePropertyName = $mapper->fixFieldName($sourcePropertyName);
                $fieldName = $mapper->getFieldMap($sourcePropertyName)?->getFieldName() ?? $sourcePropertyName;
                $fieldToProperty[$fieldName] = $sourcePropertyName;
                return $fieldName;
            },
            function ($propName, $targetName, $value) use ($mapper, $instance) {
                $fieldMap = $mapper->getFieldMap($propName);
                return $fieldMap?->getUpdateFunctionValue($value, $instance, $this->getDbDriverWrite()->getDbHelper()) ?? $value;
            }
        );
        $array = array_filter((array)$valuesToUpdate, fn($value) => $value !== false);

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
            foreach ($this->getMapper()->getFieldMap() as $mapper) {
                $fieldValue = $mapper->getInsertFunctionValue($array[$mapper->getFieldName()] ?? null, $instance, $this->getDbDriverWrite()->getDbHelper());
                if ($fieldValue !== false) {
                    $array[$mapper->getFieldName()] = $fieldValue;
                }
            }
            $updatable = InsertQuery::getInstance($this->mapper->getTable(), $array);
        } else {
            $closure = $this->beforeUpdate;
            $array = $closure($array);
            $updatable = UpdateQuery::getInstance($array, $this->mapper);
        }

        // Check if is OK
        if (empty($array)) {
            throw new OrmBeforeInvalidException('Invalid Before Insert Closure');
        }

        // Execute the Insert or Update
        if ($isInsert) {
            $keyGen = $this->getMapper()->generateKey($this->getDbDriver(), $instance) ?? [];
            if (!empty($keyGen) && !is_array($keyGen)) {
                $keyGen = [$keyGen];
            }
            $position = 0;
            foreach ($keyGen as $value) {
                $array[$pkList[$position]] = $value;
                $updatable->set($this->mapper->getPrimaryKey()[$position++], $value);
            }
            $keyReturned = $this->insert($updatable, $keyGen);
            if (count($pkList) == 1 && !empty($keyReturned)) {
                $array[$pkList[0]] = $keyReturned;
            }
        } else {
            if (!empty($updateConstraint)) {
                $updateConstraint->check($oldInstance, $this->getMapper()->getEntity($array));
            }
            $this->update($updatable);
        }

        ObjectCopy::copy($array, $instance, function ($sourcePropertyName) use ($fieldToProperty) {
            return $fieldToProperty[$sourcePropertyName] ?? $sourcePropertyName;
        });

        ORMSubject::getInstance()->notify(
            $this->mapper->getTable(),
            $isInsert ? ORMSubject::EVENT_INSERT : ORMSubject::EVENT_UPDATE,
            $instance, $oldInstance
        );

        return $instance;
    }


    /**
     * @throws InvalidArgumentException
     */
    public function addObserver(ObserverProcessorInterface $observerProcessor): void
    {
        ORMSubject::getInstance()->addObserver($observerProcessor, $this);
    }

    /**
     * @param InsertQuery $updatable
     * @param mixed $keyGen
     * @return mixed
     * @throws OrmInvalidFieldsException
     * @throws RepositoryReadOnlyException
     */
    protected function insert(InsertQuery $updatable, mixed $keyGen): mixed
    {
        if (empty($keyGen)) {
            return $this->insertWithAutoInc($updatable);
        } else {
            $this->insertWithKeyGen($updatable);
            return null;
        }
    }

    /**
     * @param InsertQuery $updatable
     * @return int
     * @throws OrmInvalidFieldsException
     * @throws RepositoryReadOnlyException
     */
    protected function insertWithAutoinc(InsertQuery $updatable): int
    {
        $sqlObject = $updatable->build($this->getDbDriverWrite()->getDbHelper());
        $dbFunctions = $this->getDbDriverWrite()->getDbHelper();
        return $dbFunctions->executeAndGetInsertedId($this->getDbDriverWrite(), $sqlObject->getSql(), $sqlObject->getParameters());
    }

    /**
     * @param InsertQuery $updatable
     * @return void
     * @throws OrmInvalidFieldsException
     * @throws RepositoryReadOnlyException
     */
    protected function insertWithKeyGen(InsertQuery $updatable): void
    {
        $sqlObject = $updatable->build($this->getDbDriverWrite()->getDbHelper());
        $this->getDbDriverWrite()->execute($sqlObject->getSql(), $sqlObject->getParameters());
    }

    /**
     * @param UpdateQuery $updatable
     * @throws InvalidArgumentException
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
