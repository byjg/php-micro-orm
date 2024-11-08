<?php

namespace ByJG\MicroOrm;

use InvalidArgumentException;

class DatabaseRelationship
{
    private array $relationships = [];

    /**
     * @var Mapper[]
     */
    private array $mapper = [];

    private array $incompleteRelationships = [];

    public function __construct(Mapper $mainMapper)
    {
        $this->mapper[$mainMapper->getTable()] = $mainMapper;
    }

    public function addRelationship(string|Mapper $parent, string|Mapper $child, string $foreignKeyName, ?string $primaryKey = '?'): void
    {
        $parentTableName = $parent;
        if (is_string($parent) && isset($this->mapper[$parent])) {
            $parent = $this->mapper[$parent];
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
        $this->saveRelationShip($parentTableName, $childTableName, $primaryKey, $foreignKeyName);

        // Store the relationship in the mapper
        if ($parent instanceof Mapper && !isset($this->mapper[$parent->getTable()])) {
            $this->mapper[$parent->getTable()] = $parent;
        }

        if ($child instanceof Mapper && !isset($this->mapper[$child->getTable()])) {
            $this->mapper[$child->getTable()] = $child;
        }
    }

    public function merge(DatabaseRelationship $relationshipFrom): void
    {
        foreach ($relationshipFrom->relationships as $key => $relationship) {
            list($parent, $child) = explode(",", $key);

            $parent = $relationship["parent"];
            $child = $relationship["child"];
            $foreignKey = $relationship["fk"];
            $primaryKey = $relationship["pk"];

            if (isset($relationshipFrom->mapper[$parent])) {
                $parent = $relationshipFrom->mapper[$parent];
            } elseif (isset($this->mapper[$parent])) {
                $parent = $this->mapper[$parent];
            }
            if (isset($relationshipFrom->mapper[$child])) {
                $child = $relationshipFrom->mapper[$child];
            } elseif (isset($this->mapper[$child])) {
                $child = $this->mapper[$child];
            }

            $this->addRelationship($parent, $child, $foreignKey, $primaryKey);
        }
    }

    public function getRelationship(string ...$tables): array
    {
        // First time we try to fix the incomplete relationships
        foreach ($this->incompleteRelationships as $key => $relationship) {
            if (isset($this->mapper[$relationship["parent"]])) {
                continue;
            }
            $this->addRelationship($relationship["parent"], $relationship["child"], $relationship["fk"]);
        }

        $result = [];

        for ($i = 0; $i < count($tables) - 1; $i++) {
            $path = $this->findRelationshipPath($tables[$i], $tables[$i + 1]);
            if ($path) {
                $result = array_merge($result, $path);
            } else {
                return []; // Return empty array if no path is found between any two tables
            }
        }

        return array_values(array_unique($result));
    }

    public function getRelationshipData(string ...$tables): array
    {
        $relationship = $this->getRelationship(...$tables);
        $result = [];

        foreach ($relationship as $item) {
            $result[] = $this->relationships[$item];
        }

        return $result;
    }

    private function findRelationshipPath(string $start, string $end): ?array
    {
        $queue = [[$start, []]];
        $visited = [];

        while (!empty($queue)) {
            list($current, $path) = array_shift($queue);
            $visited[$current] = true;

            foreach ($this->relationships as $relationshipKey => $relationshipData) {
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

    public function getQueryInstance(string ...$tables): Query
    {
        $query = new Query();

        $relationships = $this->getRelationshipData(...$tables);

        if (empty($relationships)) {
            if (count($tables) === 1) {
                $query->table($tables[0]);
                return $query;
            } else {
                throw new InvalidArgumentException("No relationship found between the tables");
            }
        }

        $deletedAt = [];

        $first = true;
        foreach ($relationships as $relationship) {
            $parent = $relationship["parent"];
            $child = $relationship["child"];
            $foreignKey = $relationship["fk"];
            $primaryKey = $relationship["pk"];

            $parentAlis = $this->mapper[$parent]->getTableAlias();
            $childAlias = $this->mapper[$child]->getTableAlias();

            if ($first) {
                $query->table($parent, $parentAlis);
                $first = false;
            }
            $query->join($child, "{$parentAlis}.{$primaryKey} = {$childAlias}.{$foreignKey}", $childAlias);

            if ($this->mapper[$parent]->getSoftDelete() && !isset($deletedAt[$parent])) {
                $deletedAt[$parent] = "{$parentAlis}.deleted_at is null";
            }

            if ($this->mapper[$child]->getSoftDelete() && !isset($deletedAt[$child])) {
                $deletedAt[$child] = "{$childAlias}.deleted_at is null";
            }
        }

        foreach ($deletedAt as $condition) {
            $query->where($condition);
        }

        return $query;
    }

    private function getNormalizedKey(string $table1, string $table2): string
    {
        return strcmp($table1, $table2) < 0 ? "$table1,$table2" : "$table2,$table1";
    }

    private function saveRelationShip(string $parentTable, string $childTable, string $primaryKey, string $foreignKey): void
    {
        // Normalize the relationship order to ensure consistency

        $data = ["pk" => $primaryKey, "fk" => $foreignKey, "parent" => $parentTable, "child" => $childTable];

        $this->relationships[$this->getNormalizedKey($parentTable, $childTable)] = $data;

        if ($primaryKey === '?') {
            $this->incompleteRelationships[$this->getNormalizedKey($parentTable, $childTable)] = $data;
        } else {
            unset($this->incompleteRelationships[$this->getNormalizedKey($parentTable, $childTable)]);
        }
    }
}
