---
sidebar_position: 13
---

# Controlling the data

You can control the data queried or updated by the micro-orm using the Mapper object.

Let's say you want to store the phone number only with numbers in the database, 
but in the entity class, you want to store with the mask.

You can add the `withUpdateFunction`, `withInsertFunction` and `withSelectFunction` to the FieldMap object
as you can see below:


```php
<?php
// Creating the mapping
$mapper = new \ByJG\MicroOrm\Mapper(...);

$fieldMap = FieldMap::create('propertyname') // The property name of the entity class
    // Returns the pre-processed value before UPDATE/INSERT the $field name
    // If the function returns NULL this field will not be included in the UPDATE/INSERT
    ->withUpdateFunction(function ($field, $instance) {
        return preg_replace('/[^0-9]/', '', $field);
    })
    // Returns the field value with a post-processed value of $field AFTER query from DB
    ->withSelectFunction(function ($field, $instance) {
        return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $field);
    })

$mapper->addFieldMapping($fieldMap);
```

You can also pass any callable function to the `withUpdateFunction`, `withInsertFunction` and `withSelectFunction`.

e.g.:

- `withUpdateFunction('myFunction')`
- `withUpdateFunction([$myObject, 'myMethod'])`
- `withUpdateFunction('MyClass::myStaticMethod')`
- `withUpdateFunction(function ($field, $instance) { return $field; })`
- `withUpdateFunction(fn($field, $instance) => $field)`
- `withUpdateFunction(MapperFunctions::NOW_UTC)` (pre-defined functions, see below)

## Arguments passed to the functions

The functions will receive three arguments:

- `$field`: The value of the field in the entity class;
- `$instance`: The instance of the entity class;
- `$dbHelper`: The instance of the DbHelper class. You can use it to convert the value to the database format.

## Pre-defined functions

| Function                              | Description                                                                                                |
|---------------------------------------|------------------------------------------------------------------------------------------------------------|
| `MapperFunctions::STANDARD`           | Defines the basic behavior for select and update fields; You don't need to set it. Just know it exists.    |
| `MapperFunctions::READ_ONLY`          | Defines a read-only field. It can be retrieved from the database but will not be updated.                  |
| `MapperFunctions::NOW_UTC`            | Returns the current date/time in UTC. It is used to set the current date/time in the database.             |
| `MapperFunctions::UPDATE_BINARY_UUID` | Converts a UUID string to a binary representation. It is used to store UUID in a binary field.             |
| `MapperFunctions::SELECT_BINARY_UUID` | Converts a binary representation of a UUID to a string. It is used to retrieve a UUID from a binary field. |

You can use them in the FieldAttribute as well:

```php 
<?php
#[FieldAttribute(
    fieldName: 'uuid',
    updateFunction: MapperFunctions::UPDATE_BINARY_UUID,
    selectFunction: MapperFunctions::SELECT_BINARY_UUID)
]
```

## Generic function to be processed before any insert or update

You can also set closure to be applied before insert or update at the record level and not only in the field level.
In this case will set in the Repository:

```php
<?php
Repository::setBeforeInsert(function ($instance) {
    return $instance;
});

Repository::setBeforeUpdate(function ($instance) {
    return $instance;
});
```

The return of the function will be the instance that will be used to insert or update the record.

