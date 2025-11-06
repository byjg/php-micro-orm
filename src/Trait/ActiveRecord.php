<?php

namespace ByJG\MicroOrm\Trait;

use ByJG\AnyDataset\Core\IteratorFilter;
use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\MicroOrm\ActiveRecordQuery;
use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\MicroOrm\Exception\OrmInvalidFieldsException;
use ByJG\MicroOrm\Mapper;
use ByJG\MicroOrm\ORM;
use ByJG\MicroOrm\Query;
use ByJG\MicroOrm\Repository;
use ByJG\Serializer\ObjectCopy;
use ByJG\Serializer\Serialize;

trait ActiveRecord
{
    protected static ?DatabaseExecutor $executor = null;

    protected static ?Repository $repository = null;

    public static function initialize(?DatabaseExecutor $executor = null)
    {
        if (!is_null(self::$executor)) {
            return;
        }

        if (is_null($executor)) {
            $executor = ORM::defaultDbDriver();
        }

        self::$executor = $executor;
        self::$repository = new Repository($executor, self::discoverClass());
    }

    public static function reset(?DatabaseExecutor $executor = null)
    {
        self::$executor = null;
        self::$repository = null;
        if (!is_null($executor)) {
            self::initialize($executor);
        }
    }

    public static function tableName(): string
    {
        self::initialize();
        return self::$repository->getMapper()->getTable();
    }

    public function save()
    {
        self::initialize();
        self::$repository->save($this);
    }

    protected function pkList(): array
    {
        self::initialize();
        $pk = self::$repository->getMapper()->getPrimaryKeyModel();

        $filter = [];
        foreach ($pk as $field) {
            $pkValue = $this->{$field};
            if (empty($pkValue)) {
                throw new OrmInvalidFieldsException("Primary key '$field' is null");
            }
            $filter[] = $this->{$field};
        }

        return $filter;
    }

    public function delete()
    {
        self::$repository->delete($this->pkList());
    }

    public static function new(mixed $data = null): static
    {
        self::initialize();
        $data = $data ?? [];
        return self::$repository->entity(Serialize::from($data)->toArray());
    }

    public static function get(mixed ...$pk)
    {
        self::initialize();
        return self::$repository->get(...$pk);
    }

    public function fill(mixed $data)
    {
        $newData = self::new($data)->toArray();
        ObjectCopy::copy($newData, $this);
    }

    public function refresh()
    {
        $this->fill(self::$repository->get(...$this->pkList()));
    }

    /**
     * @param IteratorFilter $filter
     * @param int $page
     * @param int $limit
     * @return static[]
     * @throws InvalidArgumentException
     */
    public static function filter(IteratorFilter $filter, int $page = 0, int $limit = 50): array
    {
        self::initialize();
        return self::$repository->getByFilter($filter, page: $page, limit: $limit);
    }

    public static function all(int $page = 0, int $limit = 50): array
    {
        self::initialize();
        return self::$repository->getByFilter(page: $page, limit: $limit);
    }

    public static function joinWith(string ...$tables): Query
    {
        self::initialize();
        $tables[] = self::$repository->getMapper()->getTable();
        return ORM::getQueryInstance(...$tables);
    }

    public function toArray(bool $includeNullValue = false): array
    {
        if ($includeNullValue) {
            return Serialize::from($this)->toArray();
        }

        return Serialize::from($this)->withDoNotParseNullValues()->toArray();
    }

    /**
     * @param Query $query
     * @return static[]
     */
    public static function query(Query $query): array
    {
        self::initialize();
        return self::$repository->getByQuery($query);
    }

    /**
     * Create a new query builder for this Active Record model
     *
     * @return ActiveRecordQuery
     */
    public static function newQuery(): ActiveRecordQuery
    {
        self::initialize();
        return new ActiveRecordQuery(self::$repository);
    }

    /**
     * Create a new query with an initial WHERE clause for fluent syntax
     *
     * Example: User::where('email = :email', ['email' => 'test@example.com'])->first()
     *
     * @param array|string $filter
     * @param array $params
     * @return ActiveRecordQuery
     */
    public static function where(array|string $filter, array $params = []): ActiveRecordQuery
    {
        self::initialize();
        return ActiveRecordQuery::createWhere(self::$repository, $filter, $params);
    }

    // Override this method to create a custom mapper instead of discovering by attributes in the class
    protected static function discoverClass(): string|Mapper
    {
        return static::class;
    }

}