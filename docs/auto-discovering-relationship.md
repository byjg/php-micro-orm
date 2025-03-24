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

To generate the SQL query with the relationship, you can use the `ORM` static class:

```php
<?php
$query = ORM::getQueryInstance("table1", "table2");
```

The command above will return a query object with the appropriate join, equivalent to:

```php
<?php
$query = Query::getInstance()
    ->table('table1')
    ->join('table2', 'table2.id_table1 = table1.id');
```

## Queries with Multiple Tables

You can also create queries with multiple joined tables by passing more table names:

```php
<?php
$query = ORM::getQueryInstance("table1", "table2", "table3");
```

The ORM will automatically discover the path to connect these tables if relationships have been defined.

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

