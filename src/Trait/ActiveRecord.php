<?php

namespace ByJG\MicroOrm\Trait;

use ByJG\AnyDataset\Core\IteratorFilter;
use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\MicroOrm\Exception\OrmInvalidFieldsException;
use ByJG\MicroOrm\Mapper;
use ByJG\MicroOrm\ORM;
use ByJG\MicroOrm\Query;
use ByJG\MicroOrm\Repository;
use ByJG\Serializer\ObjectCopy;
use ByJG\Serializer\Serialize;

trait ActiveRecord
{
    protected static ?DbDriverInterface $dbDriver = null;

    protected static ?Repository $repository = null;

    public static function initialize(?DbDriverInterface $dbDriver = null)
    {
        if (!is_null(self::$dbDriver)) {
            return;
        }

        if (is_null($dbDriver)) {
            $dbDriver = ORM::defaultDbDriver();
        }

        self::$dbDriver = $dbDriver;
        self::$repository = new Repository($dbDriver, self::discoverClass());
    }

    public static function reset(?DbDriverInterface $dbDriver = null)
    {
        self::$dbDriver = null;
        self::$repository = null;
        if (!is_null($dbDriver)) {
            self::initialize($dbDriver);
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

    // Override this method to create a custom mapper instead of discovering by attributes in the class
    protected static function discoverClass(): string|Mapper
    {
        return static::class;
    }

}