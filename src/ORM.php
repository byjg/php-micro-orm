<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\MicroOrm\Exception\InvalidArgumentException;

class ORM
{
    private static ?DatabaseExecutor $executor = null;
    private static array $relationships = [];

    /**
     * @var Mapper[]
     */
    private static array $mapper = [];

    private static array $incompleteRelationships = [];

    public static function addMapper(Mapper $mainMapper): void
    {
        static::$mapper[$mainMapper->getTable()] = $mainMapper;
    }

    public static function addRelationship(string|Mapper $parent, string|Mapper $child, string $foreignKeyName, ?string $primaryKey = '?'): void
    {
        if (is_string($parent) && isset(static::$mapper[$parent])) {
            $parent = static::$mapper[$parent];
        }
        if ($parent instanceof Mapper) {
            $parentTableName = $parent->getTable();
            $primaryKey = $parent->getPrimaryKey()[0] ?? null;
        } else {
            $parentTableName = $parent;
        }

        if ($child instanceof Mapper) {
            $childTableName = $child->getTable();
        } else {
            $childTableName = $child;
        }

        if ($primaryKey === null) {
            return;
        }

        // Store relationships in a standardized order (alphabetically)
        static::saveRelationShip($parentTableName, $childTableName, $primaryKey, $foreignKeyName);

        // Store the relationship in the mapper
        if ($parent instanceof Mapper && !isset(static::$mapper[$parent->getTable()])) {
            static::$mapper[$parent->getTable()] = $parent;
        }

        if ($child instanceof Mapper && !isset(static::$mapper[$child->getTable()])) {
            static::$mapper[$child->getTable()] = $child;
        }
    }

    public static function getRelationship(string ...$tables): array
    {
        // Retry incomplete relationships whose parent mapper is now registered,
        // so the parent primary key ('?') can finally be resolved.
        foreach (static::$incompleteRelationships as $relationship) {
            if (!isset(static::$mapper[$relationship["parent"]])) {
                continue;
            }
            static::addRelationship($relationship["parent"], $relationship["child"], $relationship["fk"]);
        }

        $result = [];

        for ($i = 0; $i < count($tables) - 1; $i++) {
            $path = static::findRelationshipPath($tables[$i], $tables[$i + 1]);
            if ($path) {
                $result = array_merge($result, $path);
            } else {
                return []; // Return empty array if no path is found between any two tables
            }
        }

        return array_values(array_unique($result));
    }

    public static function getRelationshipData(string ...$tables): array
    {
        $relationship = static::getRelationship(...$tables);
        $result = [];

        foreach ($relationship as $item) {
            $result[] = static::$relationships[$item];
        }

        return $result;
    }

    private static function findRelationshipPath(string $start, string $end): ?array
    {
        $queue = [[$start, []]];
        $visited = [];

        while (!empty($queue)) {
            list($current, $path) = array_shift($queue);
            $visited[$current] = true;

            foreach (static::$relationships as $relationshipKey => $relationshipData) {
                list($from, $to) = explode(",", $relationshipKey);
                if (($from === $current && !isset($visited[$to])) || ($to === $current && !isset($visited[$from]))) {
                    $neighbor = $from === $current ? $to : $from;
                    $newPath = array_merge($path, [$relationshipKey]);

                    if ($neighbor === $end) {
                        return $newPath;
                    }

                    $queue[] = [$neighbor, $newPath];
                }
            }
        }

        return null;
    }

    public static function getMapper(string $tableName): ?Mapper
    {
        return static::$mapper[$tableName] ?? null;
    }

    /**
     * Resolve a model class to its table name, registering its mapper on demand.
     *
     * Building the Mapper only reads the class attributes (reflection) — it does not
     * open a database connection — so calling this at any point simply makes the
     * entity's table and its parentTable relationships known to the ORM. This is what
     * lets joinRelated()/joinWith() take a model class and discover the relationship
     * graph on the current request, without every mapper being pre-registered.
     *
     * @param class-string $class
     */
    public static function getTableFromClass(string $class): string
    {
        foreach (static::$mapper as $mapper) {
            if ($mapper->getEntity() === $class) {
                return $mapper->getTable();
            }
        }

        return (new Mapper($class))->getTable();
    }

    private static function getNormalizedKey(string $table1, string $table2): string
    {
        return strcmp($table1, $table2) < 0 ? "$table1,$table2" : "$table2,$table1";
    }

    private static function saveRelationShip(string $parentTable, string $childTable, string $primaryKey, string $foreignKey): void
    {
        // Normalize the relationship order to ensure consistency

        $data = ["pk" => $primaryKey, "fk" => $foreignKey, "parent" => $parentTable, "child" => $childTable];

        static::$relationships[static::getNormalizedKey($parentTable, $childTable)] = $data;

        if ($primaryKey === '?') {
            static::$incompleteRelationships[static::getNormalizedKey($parentTable, $childTable)] = $data;
        } else {
            unset(static::$incompleteRelationships[static::getNormalizedKey($parentTable, $childTable)]);
        }
    }

    public static function resetMemory(): void
    {
        static::$relationships = [];
        static::$incompleteRelationships = [];
        static::$executor = null; // Reset the default DB driver
        foreach (static::$mapper as $mapper) {
            // Reset the ActiveRecord DatabaseExecutor
            if (method_exists($mapper->getEntity(), 'reset')) {
                call_user_func([$mapper->getEntity(), 'reset']);
            }
        }
        static::$mapper = [];
    }

    public static function defaultDbDriver(?DatabaseExecutor $executor = null): DatabaseExecutor
    {
        if (is_null($executor)) {
            if (is_null(static::$executor)) {
                throw new InvalidArgumentException("You must initialize the ORM with a DatabaseExecutor");
            }
            return static::$executor;
        }

        static::$executor = $executor;
        return $executor;
    }
}
