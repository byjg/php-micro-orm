<?php

namespace ByJG\MicroOrm;

use InvalidArgumentException;

class ORM
{
    private static array $relationships = [];

    /**
     * @var Mapper[]
     */
    private static array $mapper = [];

    private static array $incompleteRelationships = [];

    public static function addMapper(Mapper $mainMapper)
    {
        static::$mapper[$mainMapper->getTable()] = $mainMapper;
    }

    public static function addRelationship(string|Mapper $parent, string|Mapper $child, string $foreignKeyName, ?string $primaryKey = '?'): void
    {
        $parentTableName = $parent;
        if (is_string($parent) && isset(static::$mapper[$parent])) {
            $parent = static::$mapper[$parent];
        }
        if ($parent instanceof Mapper) {
            $parentTableName = $parent->getTable();
            $primaryKey = $parent->getPrimaryKey()[0];
        }

        $childTableName = $child;
        if ($child instanceof Mapper) {
            $childTableName = $child->getTable();
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
        // First time we try to fix the incomplete relationships
        foreach (static::$incompleteRelationships as $key => $relationship) {
            if (isset(static::$mapper[$relationship["parent"]])) {
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

    public static function getQueryInstance(string ...$tables): Query
    {
        $query = new Query();

        $relationships = static::getRelationshipData(...$tables);

        if (empty($relationships)) {
            if (count($tables) === 1) {
                $query->table($tables[0]);
                return $query;
            } else {
                throw new InvalidArgumentException("No relationship found between the tables");
            }
        }

        $first = true;
        foreach ($relationships as $relationship) {
            $parent = $relationship["parent"];
            $child = $relationship["child"];
            $foreignKey = $relationship["fk"];
            $primaryKey = $relationship["pk"];

            $parentAlis = static::$mapper[$parent]->getTableAlias();
            $childAlias = static::$mapper[$child]->getTableAlias();

            if ($first) {
                $query->table($parent, $parentAlis);
                $first = false;
            }
            $query->join($child, "{$parentAlis}.{$primaryKey} = {$childAlias}.{$foreignKey}", $childAlias);
        }

        return $query;
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

    public static function clearRelationships(): void
    {
        static::$relationships = [];
        static::$incompleteRelationships = [];
        foreach (static::$mapper as $mapper) {
            // Reset the ActiveRecord DbDriver
            if (method_exists($mapper->getEntity(), 'reset')) {
                call_user_func([$mapper->getEntity(), 'reset']);
            }
        }
        static::$mapper = [];
    }
}
