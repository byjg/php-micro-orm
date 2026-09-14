---
sidebar_position: 8
---

# Auto Discovering Relationship

The `FieldAttribute` has a parameter `parentTable` that is used to define the parent table of the field. This is used in
the case of a foreign key.

Once this attribute is set, the `Repository` can auto-discover the relationship between the tables
and generate the proper SQL with the relationship to retrieve the data.

## How to use

You can use the `parentTable` parameter in the `FieldAttribute` to define the parent table of the field.

```php
<?php
#[TableAttribute(tableName: 'table1')]
class Class1
{
    #[FieldAttribute(primaryKey: true)]
    public ?int $id;
}

#[TableAttribute(tableName: 'table2')]
class Class2
{
    #[FieldAttribute(primaryKey: true)]
    public ?int $id;

    #[FieldAttribute(fieldName: "id_table1", parentTable: "table1")]
    public ?int $idTable1;
}

$repository1 = new Repository($dbDriver, Class1::class);
$repository2 = new Repository($dbDriver, Class2::class);
```

This will automatically create the relationship between `table1` and `table2` through the ORM system's internal
relationship registry.

## Generating Queries with Relationships

Once the relationships are registered, use `Query::joinRelated()` to add a
relationship-derived join to a query. It derives the `ON` condition from the
registered `parentTable` relationship instead of writing it by hand:

```php
<?php
$query = Query::getInstance()
    ->table('table2')
    ->joinRelated('table1');
// SELECT * FROM table2 INNER JOIN table1 ON table1.id = table2.id_table1
```

`joinRelated()` appends the join to whichever table is already in the query (the base
table or a previously joined one), so it keeps your own base table. Because of that it
composes on top of an existing query — including one a `Repository` already scoped to
its table, where `$repository->getByQuery($query)` still returns that repository's
entity.

There are `joinRelated()`, `leftJoinRelated()` and `rightJoinRelated()`, mirroring
`join()`/`leftJoin()`/`rightJoin()`, so you can pick the join type.

If the target table is **not directly related** to a table already in the query, the
intermediate tables on the shortest relationship path are joined automatically — you
don't have to remember them (though keep in mind the extra joins have a cost). Tables
already in the query are skipped, so chaining stays consistent:

```php
<?php
// table1 -> table2 -> table4: table2 is joined for you.
$query = Query::getInstance()->table('table1')->joinRelated('table4');
// SELECT * FROM table1
//   INNER JOIN table2 ON table1.id = table2.id_table1
//   INNER JOIN table4 ON table2.id = table4.id_table2

// Naming the intermediate explicitly gives the same result (table2 is not joined twice):
$query = Query::getInstance()->table('table1')->joinRelated('table2')->joinRelated('table4');
```

For a `left`/`rightJoinRelated()` that spans intermediates, every hop it adds uses that
join type. `joinRelated()` throws an `InvalidArgumentException` only when no relationship
path connects the tables. For aliased joins, use `join()`/`leftJoin()`/`rightJoin()` with
an explicit `ON`.

Active Record models expose the same through `Model::joinWith(...$tables)`, which starts
from the model's own table and joins the related ones.

### Passing a model class (on-demand mapper registration)

`joinRelated()` (and `joinWith()`) accept a **model class** as well as a table name.
Relationship discovery walks the ORM's registry of mappers, and a mapper is only known
after it has been built. On a request that only touched one entity, the other entities'
mappers are not registered yet, so their relationships are invisible. Passing a class
fixes that: it registers that entity's mapper on demand — this reads the class attributes
(reflection) only and does **not** open a database connection — before resolving the join.

```php
<?php
// 'project' was never referenced on this request, so its mapper is not registered.
$query = Query::getInstance()->table('task')->joinRelated(Project::class);
// Project's mapper is registered on demand, then:
// SELECT * FROM task INNER JOIN project ON project.id = task.project_id
```

To span a **hidden intermediate**, name each entity in the path — the same way Eloquent's
`hasManyThrough` names its through-model. Auto-discovery can only join an intermediate
whose mapper is registered, so naming it is what makes it known:

```php
<?php
// note -> task -> project. A note has no project_id, so 'task' is the through-entity.
$notes = Note::joinWith(Task::class, Project::class)
    ->field('note.*')
    ->where('project.id = :id', ['id' => $projectId]);
// FROM note INNER JOIN task ON task.id = note.task_id
//           INNER JOIN project ON project.id = task.project_id
```

`ORM::getTableFromClass(Model::class)` exposes the same resolution directly, returning the
table name and registering the mapper if needed.

## Manual Relationship Definition

If you need to define relationships manually (without using attributes), you can use the `addRelationship` method:

```php
<?php
ORM::addRelationship("table1", "table2", "id_table1", "id");
```

This defines a relationship where `table2.id_table1` is a foreign key that references `table1.id`.

You can also use Mapper objects:

```php
<?php
$mapper1 = new Mapper(Class1::class);
$mapper2 = new Mapper(Class2::class);

ORM::addRelationship($mapper1, $mapper2, "id_table1");
```

In this case, the primary key will be automatically determined from the first mapper.

## Getting Relationship Information

You can get information about relationships between tables:

```php
<?php
// Get relationship keys
$relationships = ORM::getRelationship("table1", "table2");

// Get detailed relationship data
$relationshipData = ORM::getRelationshipData("table1", "table2");
```

### Return Value of getRelationship

The `ORM::getRelationship()` method returns an array of relationship keys that define the path between the specified
tables. Each key in the array is a string formatted as "tableA,tableB" which identifies a specific relationship in the
internal relationship registry.

For example, if you call:

```php
$relationships = ORM::getRelationship("table1", "table2");
```

It might return something like:

```php
["table1,table2"]
```

If there are multiple tables involved in a path (like when finding relationships between more distant tables), it would
return all the relationship keys in the path, like:

```php
["table1,intermediateTable", "intermediateTable,table2"]
```

These keys identify the relationships in the internal registry and are used to retrieve the actual relationship details.

### Return Value of getRelationshipData

The `ORM::getRelationshipData()` method returns the actual detailed relationship information for the tables. It takes
the keys returned by `getRelationship()` and retrieves the corresponding relationship data objects.

The method returns an array of relationship data objects, where each object contains:

- `parent`: The parent table name
- `child`: The child table name
- `pk`: The primary key in the parent table
- `fk`: The foreign key in the child table

For example:

```php
$relationshipData = ORM::getRelationshipData("table1", "table2");
```

Might return:

```php
[
    [
        "parent" => "table1",
        "child" => "table2",
        "pk" => "id",
        "fk" => "id_table1"
    ]
]
```

This detailed information is used internally when automatically constructing joins in queries between the tables.

## Limitations

- This feature does not support multiple relationships between the same tables
- Primary keys with two or more fields are not fully supported for auto-relationship discovery

