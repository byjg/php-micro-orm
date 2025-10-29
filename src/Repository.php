<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Core\AnyDataset;
use ByJG\AnyDataset\Core\Enum\Relation;
use ByJG\AnyDataset\Core\Exception\DatabaseException;
use ByJG\AnyDataset\Core\GenericIterator;
use ByJG\AnyDataset\Core\IteratorFilter;
use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\AnyDataset\Db\Exception\DbDriverNotConnected;
use ByJG\AnyDataset\Db\IsolationLevelEnum;
use ByJG\AnyDataset\Db\IteratorFilterSqlFormatter;
use ByJG\AnyDataset\Db\SqlStatement;
use ByJG\MicroOrm\Enum\ObserverEvent;
use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\MicroOrm\Exception\OrmBeforeInvalidException;
use ByJG\MicroOrm\Exception\OrmInvalidFieldsException;
use ByJG\MicroOrm\Exception\OrmModelInvalidException;
use ByJG\MicroOrm\Exception\RepositoryReadOnlyException;
use ByJG\MicroOrm\Exception\UpdateConstraintException;
use ByJG\MicroOrm\Interface\EntityProcessorInterface;
use ByJG\MicroOrm\Interface\ObserverProcessorInterface;
use ByJG\MicroOrm\Interface\QueryBuilderInterface;
use ByJG\MicroOrm\Interface\UpdateConstraintInterface;
use ByJG\MicroOrm\Literal\Literal;
use ByJG\MicroOrm\Literal\LiteralInterface;
use ByJG\MicroOrm\PropertyHandler\MapFromDbToInstanceHandler;
use ByJG\MicroOrm\PropertyHandler\PrepareToUpdateHandler;
use ByJG\Serializer\ObjectCopy;
use ByJG\Serializer\Serialize;
use ByJG\XmlUtil\Exception\FileException;
use ByJG\XmlUtil\Exception\XmlUtilException;
use Exception;
use ReflectionException;
use stdClass;
use Throwable;

class Repository
{

    /**
     * @var Mapper
     */
    protected Mapper $mapper;

    /**
     * @var DatabaseExecutor
     */
    protected DatabaseExecutor $dbDriver;

    /**
     * @var DatabaseExecutor|null
     */
    protected ?DatabaseExecutor $dbDriverWrite = null;

    /**
     * @var EntityProcessorInterface|null
     */
    protected EntityProcessorInterface|null $beforeUpdate = null;

    /**
     * @var EntityProcessorInterface|null
     */
    protected EntityProcessorInterface|null $beforeInsert = null;

    /**
     * Repository constructor.
     * @param DatabaseExecutor $executor
     * @param string|Mapper $mapperOrEntity
     * @throws InvalidArgumentException
     * @throws OrmModelInvalidException
     * @throws ReflectionException
     */
    public function __construct(DatabaseExecutor $executor, string|Mapper $mapperOrEntity)
    {
        $this->dbDriver = $executor;
        $this->dbDriverWrite = $executor;
        if (is_string($mapperOrEntity)) {
            $mapperOrEntity = new Mapper($mapperOrEntity);
        }
        $this->mapper = $mapperOrEntity;

        // Set beforeInsert and beforeUpdate from mapper if available
        if (!empty($this->mapper->getBeforeInsert())) {
            $this->setBeforeInsert($this->mapper->getBeforeInsert());
        }
        if (!empty($this->mapper->getBeforeUpdate())) {
            $this->setBeforeUpdate($this->mapper->getBeforeUpdate());
        }
    }

    public function addDbDriverForWrite(DatabaseExecutor $executor): void
    {
        $this->dbDriverWrite = $executor;
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

    public function getExecutor(): DatabaseExecutor
    {
        return $this->dbDriver;

    }

    /**
     * @return DatabaseExecutor
     * @throws RepositoryReadOnlyException
     */
    public function getExecutorWrite(): DatabaseExecutor
    {
        if (empty($this->dbDriverWrite)) {
            throw new RepositoryReadOnlyException('Repository is ReadOnly');
        }
        return $this->dbDriverWrite;
    }

    public function entity(array $values): mixed
    {
        return $this->getMapper()->getEntity($values);
    }

    public function queryInstance(?object $model = null): Query
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
                ->set('deleted_at', new Literal($this->getExecutorWrite()->getHelper()->sqlDate('Y-m-d H:i:s')))
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
     * Execute multiple write queries (insert/update/delete) sequentially within a transaction.
     * Invalid entries are ignored silently. If any execution fails, the transaction is rolled back.
     *
     * @param array<int, Updatable|QueryBuilderInterface> $queries List of queries to be executed in bulk
     * @param IsolationLevelEnum|null $isolationLevel
     * @return GenericIterator|null
     * @throws InvalidArgumentException
     * @throws RepositoryReadOnlyException
     * @throws Throwable
     */
    public function bulkExecute(array $queries, ?IsolationLevelEnum $isolationLevel = null): ?GenericIterator
    {
        if (empty($queries)) {
            throw new InvalidArgumentException('You pass an empty array to bulk');
        }

        $dbDriver = $this->getExecutor()->getDriver();

        $bigSqlWrites = '';
        $selectSql = null;
        $selectParams = [];
        $bigParams = [];

        foreach ($queries as $i => $query) {
            if (!($query instanceof QueryBuilderInterface) && !($query instanceof Updatable)) {
                throw new InvalidArgumentException('Invalid query type. Expected QueryBuilderInterface or Updatable.');
            }

            // Build SQL object using the write driver to ensure correct helper/dialect
            $sqlStatement = $query->build($dbDriver);
            $sql = $sqlStatement->getSql();
            $params = $sqlStatement->getParams();
            $isSelect = str_starts_with(strtoupper(ltrim($sql)), 'SELECT');

            if ($isSelect && $i === array_key_last($queries)) {
                // Trailing SELECT: keep it separate with its own params
                $selectSql = rtrim($sql, "; \t\n\r\0\x0B");
                $selectParams = $params;
                continue;
            }

            // For write statements, avoid parameter name collisions by uniquifying named params
            foreach ($params as $key => $value) {
                // Only process named parameters (string keys)
                if (isset($bigParams[$key])) {
                    $uniqueKey = $key . '__b' . $i;
                    // Replace ":key" with ":key__b{i}" using a safe regex that avoids partial matches
                    $pattern = '/(?<!:):' . preg_quote($key, '/') . '(?![A-Za-z0-9_])/';
                    $replacement = ':' . $uniqueKey;
                    $sql = preg_replace($pattern, $replacement, $sql);
                    $bigParams[$uniqueKey] = $value;
                } else {
                    // Positional parameter or numeric key; just carry over
                    $bigParams[$key] = $value;
                }
            }

            $bigSqlWrites .= rtrim($sql, "; \t\n\r\0\x0B") . ";\n";
        }

        $dbDriver->beginTransaction($isolationLevel, allowJoin: true);
        try {
            // First execute all writes (if any) in a single batch using direct PDO exec
            if (trim($bigSqlWrites) !== '') {
                // Use direct PDO to ensure multi-statement execution across drivers like SQLite
                DatabaseExecutor::using($dbDriver)->execute($bigSqlWrites, $bigParams);
            }

            // If there is a trailing SELECT, fetch it and return its iterator. Otherwise return an empty iterator
            if (!empty($selectSql)) {
                $it = DatabaseExecutor::using($dbDriver)->getIterator(new SqlStatement($selectSql, $selectParams));
            } else {
                $it = (new AnyDataset())->getIterator();
            }

            $dbDriver->commitTransaction();

            return $it;
        } catch (Exception $ex) {
            $dbDriver->rollbackTransaction();
            throw $ex;
        }
    }

    /**
     * @param DeleteQuery $updatable
     * @return bool
     * @throws InvalidArgumentException
     * @throws RepositoryReadOnlyException
     * @throws DatabaseException
     * @throws DbDriverNotConnected
     */
    public function deleteByQuery(DeleteQuery $updatable): bool
    {
        $sqlStatement = $updatable->build();

        $this->getExecutorWrite()->execute($sqlStatement);

        ORMSubject::getInstance()->notify($this->mapper->getTable(), ObserverEvent::Delete, null, $sqlStatement->getParams());

        return true;
    }

    /**
     * @param string|IteratorFilter $filter
     * @param array $params
     * @param bool $forUpdate
     * @param int $page
     * @param int|null $limit
     * @return array
     * @throws InvalidArgumentException
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
     * @throws InvalidArgumentException
     */
    public function filterIn(array|string|int|LiteralInterface $arrValues, string $field = ""): array
    {
        $arrValues = (array) $arrValues;

        if (empty($field)) {
            $field = $this->getMapper()->getPrimaryKey()[0];
        }

        $iteratorFilter = new IteratorFilter();
        $iteratorFilter->and($field, Relation::IN, $arrValues);

        return $this->getByFilter($iteratorFilter);
    }

    /**
     * @param QueryBuilderInterface $query
     * @return mixed
     */
    public function getScalar(QueryBuilderInterface $query): mixed
    {
        $sqlBuild = $query->build($this->getExecutor()->getDriver());
        return $this->getExecutor()->getScalar($sqlBuild);
    }

    /**
     * Execute a query and return an iterator with automatic entity transformation.
     *
     * This is the PREFERRED method for application/business logic when working with domain entities.
     * The iterator automatically transforms database rows into entity instances using the repository's mapper.
     *
     * @param QueryBuilderInterface|SqlStatement $query The query to execute
     * @param CacheQueryResult|null $cache Optional cache configuration
     * @return GenericIterator Iterator with entity transformation enabled
     *
     * @throws DatabaseException
     * @throws DbDriverNotConnected
     * @throws FileException
     * @throws XmlUtilException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @see Query::buildAndGetIterator() For infrastructure-level raw data access
     * @see getByQuery() For multi-mapper queries with JOIN operations
     */
    public function getIterator(QueryBuilderInterface|SqlStatement $query, ?CacheQueryResult $cache = null): GenericIterator
    {
        if ($query instanceof QueryBuilderInterface) {
            $sqlStatement = $query->build($this->getExecutor()->getDriver())
                ->withEntityClass($this->mapper->getEntityClass())
                ->withEntityTransformer(new MapFromDbToInstanceHandler($this->mapper));
        } else {
            $sqlStatement = $query;
        }

        if (!empty($cache)) {
            $sqlStatement = $sqlStatement->withCache($cache->getCache(), $cache->getCacheKey(), $cache->getTtl());
        }

        return $this->getExecutor()->getIterator($sqlStatement);
    }

    /**
     * Execute a query and return entities, with support for complex JOIN queries using multiple mappers.
     *
     * This method intelligently handles both single-mapper and multi-mapper scenarios:
     *
     * @param QueryBuilderInterface $query The query to execute (typically with JOINs for multi-mapper)
     * @param Mapper[] $mapper Additional mappers for JOIN queries (repository's mapper is always included first)
     * @param CacheQueryResult|null $cache Optional cache configuration
     * @return array For single mapper: Entity[]. For multi-mapper: Array<int, Entity[]>
     *
     * @throws DatabaseException
     * @throws DbDriverNotConnected
     * @throws FileException
     * @throws XmlUtilException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @see getIterator() For simple single-entity queries (more efficient)
     *
     * Example (single mapper):
     * ```php
     * $query = $userRepo->queryInstance()->where('status = :status', ['status' => 'active']);
     * $users = $userRepo->getByQuery($query); // Returns User[]
     * ```
     *
     * Example (multi-mapper JOIN):
     * ```php
     * $query = Query::getInstance()
     *     ->table('users')
     *     ->join('info', 'users.id = info.iduser')
     *     ->where('users.id = :id', ['id' => 1]);
     * $result = $userRepo->getByQuery($query, [$infoMapper]);
     * // Returns [[$userEntity, $infoEntity], [$userEntity, $infoEntity], ...]
     * ```
     */
    public function getByQuery(QueryBuilderInterface $query, array $mapper = [], ?CacheQueryResult $cache = null): array
    {
        $mapper = array_merge([$this->mapper], $mapper);

        // If only one mapper, use entity transformation in iterator
        if (count($mapper) === 1) {
            $iterator = $this->getIterator($query, $cache);
            return $iterator->toEntities();
        }

        // For multiple mappers, get raw data without transformation
        // (Entity transformation would fail because joined rows don't match single entity structure)
        $sqlBuild = $query->build($this->getExecutor()->getDriver());
        if (!empty($cache)) {
            $sqlBuild = $sqlBuild->withCache($cache->getCache(), $cache->getCacheKey(), $cache->getTtl());
        }
        $iterator = $this->getExecutor()->getIterator($sqlBuild);

        // Manually map each row to multiple entities
        $result = [];
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
        $sqlStatement = $query->build($this->getExecutor()->getDriver());
        $iterator = $this->getExecutor()->getIterator($sqlStatement);
        return $iterator->toArray();
    }

    /**
     * Save an instance to the database
     *
     * @param mixed $instance The instance to save
     * @param UpdateConstraintInterface|UpdateConstraintInterface[]|null $updateConstraints One or more constraints to apply
     * @return mixed The saved instance
     * @throws InvalidArgumentException
     * @throws OrmBeforeInvalidException
     * @throws OrmInvalidFieldsException
     * @throws RepositoryReadOnlyException
     * @throws UpdateConstraintException
     */
    public function save(mixed $instance, UpdateConstraintInterface|array|null $updateConstraints = null): mixed
    {
        // Build the updatable without executing
        [$updatable, $array, $fieldToProperty, $isInsert, $oldInstance, $pkList] = $this->saveUpdatableInternal($instance);

        // Execute the Insert or Update
        if ($isInsert) {
            $keyGen = $this->getMapper()->generateKey($this->getExecutorWrite(), $instance) ?? [];
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
        }

        // The command below is to get all properties of the class.
        // This will allow to process all properties, even if they are not in the $fieldValues array.
        // Particularly useful for processing the selectFunction.
        $array = array_merge(Serialize::from($instance)->toArray(), $array);
        ObjectCopy::copy($array, $instance, new MapFromDbToInstanceHandler($this->mapper));

        if (!$isInsert) {
            if (!empty($updateConstraints)) {
                // Convert single constraint to array for uniform processing
                $constraints = is_array($updateConstraints) ? $updateConstraints : [$updateConstraints];

                // Apply all constraints
                foreach ($constraints as $constraint) {
                    $constraint->check($oldInstance, $instance);
                }
            }
            $this->update($updatable);
        }


        ORMSubject::getInstance()->notify(
            $this->mapper->getTable(),
            $isInsert ? ObserverEvent::Insert : ObserverEvent::Update,
            $instance, $oldInstance
        );

        return $instance;
    }

    /**
     * Build and return the updatable (InsertQuery or UpdateQuery) without executing it.
     * This method mirrors the preparatory stage of save() and can be used to inspect or
     * bulk-compose updates prior to execution.
     *
     * @param mixed $instance
     * @return Updatable
     * @throws InvalidArgumentException
     * @throws OrmBeforeInvalidException
     * @throws RepositoryReadOnlyException
     */
    public function saveUpdatable(mixed $instance): Updatable
    {
        [$updatable] = $this->saveUpdatableInternal($instance);
        return $updatable;
    }

    /**
     * Internal helper that prepares the updatable and returns additional context
     * needed by save().
     *
     * @param mixed $instance
     * @return array [Updatable $updatable, array $array, array $fieldToProperty, bool $isInsert, mixed $oldInstance, array $pkList]
     * @throws InvalidArgumentException
     * @throws OrmBeforeInvalidException
     * @throws RepositoryReadOnlyException
     */
    protected function saveUpdatableInternal(mixed $instance): array
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
            new PrepareToUpdateHandler($mapper, $instance, $this->getExecutorWrite())
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
                return $array[$item] ?? null;
            }, $pkList);
            if (!in_array(null, $fields, true)) {
                $oldInstance = $this->get($fields);
            }
        }
        $isInsert = empty($oldInstance);

        // Execute Before Statements
        if ($isInsert) {
            if (!empty($this->beforeInsert)) {
                $array = $this->beforeInsert->process($array);
            }
            foreach ($this->getMapper()->getFieldMap() as $fieldMap) {
                $fieldValue = $fieldMap->getInsertFunctionValue($array[$fieldMap->getFieldName()] ?? null, $instance, $this->getExecutorWrite()->getHelper());
                if ($fieldValue !== false) {
                    $array[$fieldMap->getFieldName()] = $fieldValue;
                }
            }
            $updatable = InsertQuery::getInstance($this->mapper->getTable(), $array);
        } else {
            if (!empty($this->beforeUpdate)) {
                $array = $this->beforeUpdate->process($array);
            }
            $updatable = UpdateQuery::getInstance($array, $this->mapper);
        }

        // Check if is OK
        if (empty($array)) {
            throw new OrmBeforeInvalidException('Invalid Before Insert Closure');
        }

        return [$updatable, $array, $fieldToProperty, $isInsert, $oldInstance, $pkList];
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
     * @throws DatabaseException
     * @throws DbDriverNotConnected
     * @throws OrmInvalidFieldsException
     * @throws RepositoryReadOnlyException
     */
    protected function insert(InsertQuery $updatable, mixed $keyGen): mixed
    {
        if (empty($keyGen)) {
            return $this->insertWithAutoinc($updatable);
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
    protected function insertWithAutoInc(InsertQuery $updatable): int
    {
        $dbFunctions = $this->getExecutorWrite()->getHelper();
        $sqlStatement = $updatable->build($dbFunctions);
        return $dbFunctions->executeAndGetInsertedId($this->getExecutorWrite(), $sqlStatement);
    }

    /**
     * @param InsertQuery $updatable
     * @return void
     * @throws DatabaseException
     * @throws DbDriverNotConnected
     * @throws OrmInvalidFieldsException
     * @throws RepositoryReadOnlyException
     */
    protected function insertWithKeyGen(InsertQuery $updatable): void
    {
        $sqlStatement = $updatable->build($this->getExecutorWrite()->getHelper());
        $this->getExecutorWrite()->execute($sqlStatement);
    }

    /**
     * @param UpdateQuery $updatable
     * @throws DatabaseException
     * @throws DbDriverNotConnected
     * @throws InvalidArgumentException
     * @throws RepositoryReadOnlyException
     */
    protected function update(UpdateQuery $updatable): void
    {
        $sqlStatement = $updatable->build($this->getExecutorWrite()->getHelper());
        $this->getExecutorWrite()->execute($sqlStatement);
    }

    /**
     * Sets a processor to be executed before updating an entity
     *
     * @param EntityProcessorInterface|string $processor The processor to execute
     * @return void
     */
    public function setBeforeUpdate(EntityProcessorInterface|string $processor): void
    {
        if (is_string($processor)) {
            if (!class_exists($processor)) {
                throw new InvalidArgumentException("The class '$processor' does not exist");
            }

            if (!in_array(EntityProcessorInterface::class, class_implements($processor))) {
                throw new InvalidArgumentException("The class '$processor' must implement EntityProcessorInterface");
            }

            $processor = new $processor();
        }
        $this->beforeUpdate = $processor;
    }

    /**
     * Sets a processor to be executed before inserting an entity
     *
     * @param EntityProcessorInterface|string $processor The processor to execute
     * @return void
     */
    public function setBeforeInsert(EntityProcessorInterface|string $processor): void
    {
        if (is_string($processor)) {
            if (!class_exists($processor)) {
                throw new InvalidArgumentException("The class '$processor' does not exist");
            }

            if (!in_array(EntityProcessorInterface::class, class_implements($processor))) {
                throw new InvalidArgumentException("The class '$processor' must implement EntityProcessorInterface");
            }

            $processor = new $processor();
        }
        $this->beforeInsert = $processor;
    }
}
