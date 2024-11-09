# Soft Deletes

Soft deletes are a way to "delete" a record without actually removing it from the database.
This is useful for keeping a record of the data that was deleted, and for maintaining referential integrity
in the database.

The get this working automatically you need to define a field named `deleted_at` in your table **and**
create a Object Mapper that supports this field.

## How to Enable Soft Delete

You just define in the field mapper the field named `deleted_at` and the repository will automatically will
filter the records that have this field set.

There is some ways to define the field `deleted_at`:

### Using the `DeletedAt` trait in your model

```php
<?php
use ByJG\MicroOrm\Mapper\FieldAttribute;
use ByJG\MicroOrm\Attributes\TableAttribute;

#[TableAttribute(tableName: 'my_table')]
class MyModel
{
    use ByJG\MicroOrm\Mapper\DeletedAt;

    #[FieldAttribute(fieldName: 'id', primaryKey: true)]
    public ?int $id;

    #[FieldAttribute(fieldName: 'name')]
    public ?string $name;
}
```

### Using the `FieldAttribute` annotation

```php
<?php
use ByJG\MicroOrm\Mapper\FieldAttribute;
use ByJG\MicroOrm\Attributes\TableAttribute;

#[TableAttribute(tableName: 'my_table')]
class MyModel
{
    #[FieldAttribute(fieldName: 'id', primaryKey: true)]
    public ?int $id;

    #[FieldAttribute(fieldName: 'name')]
    public ?string $name;

    #[FieldAttribute(fieldName: 'deleted_at', syncWithDb: false)]
    public ?string $deletedAt;
}
```

### Using the `FieldMap` class

```php
<?php
use ByJG\MicroOrm\Mapper;
use ByJG\MicroOrm\Mapper\FieldMapper;
use ByJG\MicroOrm\MapperFunctions;

class MyModel
{
    public ?int $id;
    public ?string $name;
    public ?string $deletedAt;
}

// Creating the mapping
$mapper = new Mapper(
    MyModel::class,   // The full qualified name of the class
    'my_table',        // The table that represents this entity
    'id'            // The primary key field
);

// Optionally you can define table mappings between the propoerties
// and the database fields;
// The example below will map the property 'createdate' to the database field 'created';
$mapper->addFieldMapping(
    FieldMap::create('deletedAt')
        ->withFieldName('deleted_at')
        ->withUpdateFunction(MapperFunctions::READ_ONLY));

```

## Disabling Soft Delete Temporarily

Once one of the methods above is used, the soft delete is enabled by default. If you want to disable it temporarily
you can add to your query the argument `unsafe()`.

```php
<?php
$query = Query::getInstance();

// This will not return the records are marked as deleted 
$query->table('my_table');
    
// This will return all records, including the ones marked as deleted
$query->table('my_table')->unsafe();
```
