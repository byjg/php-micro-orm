---
sidebar_position: 13
---

# Controlling the data

You can control the data queried or updated by the micro-orm using the Mapper object.

Let's say you want to store the phone number only with numbers in the database, 
but in the entity class, you want to store with the mask.

You can add the `withUpdateFunction`, `withInsertFunction` and `withSelectFunction` to the FieldMapping object.
These methods accept either a class name string or an instance of `MapperFunctionInterface`:

```php
<?php
use ByJG\MicroOrm\Interface\MapperFunctionInterface;
use ByJG\MicroOrm\MapperFunctions\NowUtcMapper;
use ByJG\AnyDataset\Db\DbFunctionsInterface;

// Creating the mapping
$mapper = new \ByJG\MicroOrm\Mapper(...);

// Custom mapper class implementing MapperFunctionInterface
class PhoneNumberFormatMapper implements MapperFunctionInterface
{
    public function processedValue(mixed $value, mixed $instance, ?DbFunctionsInterface $helper = null): mixed
    {
        // Remove non-numeric characters before storing
        return preg_replace('/[^0-9]/', '', $value);
    }
}

class PhoneNumberDisplayMapper implements MapperFunctionInterface
{
    public function processedValue(mixed $value, mixed $instance, ?DbFunctionsInterface $helper = null): mixed
    {
        // Format as (XX) XXXX-XXXX when retrieving
        return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $value);
    }
}

// Use the mapper classes with FieldMapping
$fieldMap = \ByJG\MicroOrm\FieldMapping::create('phone') // The property name of the entity class
    // Class name as string
    ->withUpdateFunction(PhoneNumberFormatMapper::class)
    // Or direct instance
    ->withSelectFunction(new PhoneNumberDisplayMapper());

$mapper->addFieldMapping($fieldMap);
```

## Accepted Types for Mapper Functions

The `withUpdateFunction`, `withInsertFunction`, and `withSelectFunction` methods only accept:

1. A string containing a class name that implements `MapperFunctionInterface`
2. An instance of a class that implements `MapperFunctionInterface`

```php
// Valid examples:
->withUpdateFunction(NowUtcMapper::class)            // Class name as string
->withUpdateFunction(new CustomMapperClass())        // Class instance
```

Unlike earlier versions, **you cannot pass a closure or callable directly**. You must create a class implementing
`MapperFunctionInterface`.

## Arguments passed to the processedValue method

The `processedValue` method in your custom mapper class will receive three arguments:

- `$value`: The value of the field in the entity class
- `$instance`: The instance of the entity class
- `$dbHelper`: The instance of the DbFunctionsInterface. You can use it to convert the value to the database format

## Pre-defined functions

| Class                    | Description                                                                                                |
|--------------------------|------------------------------------------------------------------------------------------------------------|
| `StandardMapper`         | Defines the basic behavior for select and update fields; You don't need to set it. Just know it exists.    |
| `ReadOnlyMapper`         | Defines a read-only field. It can be retrieved from the database but will not be updated.                  |
| `NowUtcMapper`           | Returns the current date/time in UTC. It is used to set the current date/time in the database.             |
| `UpdateBinaryUuidMapper` | Converts a UUID string to a binary representation. It is used to store UUID in a binary field.             |
| `SelectBinaryUuidMapper` | Converts a binary representation of a UUID to a string. It is used to retrieve a UUID from a binary field. |

You can use them in the FieldAttribute as well:

```php
<?php
use ByJG\MicroOrm\Attributes\FieldAttribute;
use ByJG\MicroOrm\MapperFunctions\ReadOnlyMapper;

#[FieldAttribute(updateFunction: ReadOnlyMapper::class)]
protected $field;
```

For more detailed information about mapper functions, see [Mapper Functions](mapper-functions.md).

## Creating a Custom Mapper for Simple Transformations

If you need a simple transformation, you can create a lightweight mapper class:

```php
<?php
use ByJG\MicroOrm\Interface\MapperFunctionInterface;
use ByJG\AnyDataset\Db\DbFunctionsInterface;

class TrimMapper implements MapperFunctionInterface
{
    public function processedValue(mixed $value, mixed $instance, ?DbFunctionsInterface $helper = null): mixed
    {
        if (is_string($value)) {
            return trim($value);
        }
        return $value;
    }
}

// Then use it with FieldMapping
$fieldMap = \ByJG\MicroOrm\FieldMapping::create('name')
    ->withUpdateFunction(TrimMapper::class);
```

## Generic function to be processed before any insert or update

You can also set closure to be applied before insert or update at the record level and not only in the field level.
In this case set in the Repository:

```php
<?php
// Apply to a specific repository instance
$repository->setBeforeInsert(function ($instance) {
    // Manipulate the instance before insert
    return $instance;
});

$repository->setBeforeUpdate(function ($instance) {
    // Manipulate the instance before update
    return $instance;
});

// Or apply globally to all repository instances
\ByJG\MicroOrm\Repository::setBeforeInsert(function ($instance) {
    return $instance;
});

\ByJG\MicroOrm\Repository::setBeforeUpdate(function ($instance) {
    return $instance;
});
```

The return of the function will be the instance that will be used to insert or update the record.

