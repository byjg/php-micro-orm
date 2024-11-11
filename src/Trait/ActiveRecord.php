<?php

namespace ByJG\MicroOrm\Trait;

use ByJG\AnyDataset\Core\IteratorFilter;
use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\MicroOrm\ORM;
use ByJG\MicroOrm\Query;
use ByJG\MicroOrm\Repository;

trait ActiveRecord
{
    protected static ?DbDriverInterface $dbDriver = null;

    protected static ?Repository $repository = null;

    public static function initialize(DbDriverInterface $dbDriver)
    {
        self::$dbDriver = $dbDriver;

        self::$repository = new Repository($dbDriver, static::class);
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
     * @return static[]
     */
    public static function filter(IteratorFilter $filter): array
    {
        return self::$repository->getByFilter($filter);
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

}