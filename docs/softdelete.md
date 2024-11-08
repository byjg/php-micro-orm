# Soft Deletes

Soft deletes are a way to "delete" a record without actually removing it from the database.
This is useful for keeping a record of the data that was deleted, and for maintaining referential integrity
in the database.

**NOTE:**

```
SOFT DELETE ARE PARTIALLY IMPLEMENTED ON SOME METHOD. PLEASE LOOK THE REFERENCE TABLE BEFORE CONTINUE
```

## How to use

You just define in the field mapper the field named `deleted_at` and the repository will automatically will
filter the records that have this field set.

There is some ways to define the field `deleted_at`:

### Using the `DeletedAt` trait

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

### Using the `FieldMapper` class

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

## Methods that support soft delete

### Repository::class

| Method        | Support | Note                                                                 |
|:--------------|:-------:|:---------------------------------------------------------------------|
| queryInstance |   YES   | It will return a `Query::class` with the filter `deleted_at is null` |
| get           |   YES   | Get a record by PK where `deleted_at is null`                        |
| delete        |   YES   | Soft Delete a record by PK, setting `deleted_at = now()`             |
| getByFilter   |   YES   | It will return an array where `deleted_at is null`                   |
| deleteByQuery |   NO    | -                                                                    |
| getScalar     |   NO    | -                                                                    |
| getByQuery    |   NO    | -                                                                    |
| update        |   NO    | -                                                                    |

## How Handle cases where Soft Delete is not supported

If you are using a method that does not support soft delete, you'll need to manually filter the records
where `deleted_at is null`

