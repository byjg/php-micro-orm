<?php

namespace ByJG\MicroOrm\Trait;

use ByJG\AnyDataset\Core\IteratorFilter;
use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\MicroOrm\Mapper;
use ByJG\MicroOrm\ORM;
use ByJG\MicroOrm\Query;
use ByJG\MicroOrm\Repository;

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
        return self::$repository->getMapper()->getTable();
    }

    public function save()
    {
        self::$repository->save($this);
    }

    public function delete()
    {
        $pk = self::$repository->getMapper()->getPrimaryKeyModel();

        $filter = [];
        foreach ($pk as $field) {
            $filter[] = $this->{$field};
        }

        self::$repository->delete($filter);
    }

    public static function new(array $data): static
    {
        return self::$repository->entity($data);
    }

    public static function get(mixed ...$pk)
    {
        return self::$repository->get(...$pk);
    }

    /**
     * @param IteratorFilter $filter
     * @param int $page
     * @param int $limit
     * @return static[]
     */
    public static function filter(IteratorFilter $filter, int $page = 0, int $limit = 50): array
    {
        return self::$repository->getByFilter($filter, page: $page, limit: $limit);
    }

    public static function all(int $page = 0, int $limit = 50): array
    {
        return self::$repository->getByFilter(page: $page, limit: $limit);
    }

    public static function joinWith(string ...$tables): Query
    {
        $tables[] = self::$repository->getMapper()->getTable();
        return ORM::getQueryInstance(...$tables);
    }

    /**
     * @param Query $query
     * @return static[]
     */
    public static function query(Query $query): array
    {
        return self::$repository->getByQuery($query);
    }

    // Override this method to create a custom mapper instead of discovering by attributes in the class
    protected static function discoverClass(): string|Mapper
    {
        return static::class;
    }

}