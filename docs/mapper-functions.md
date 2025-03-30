---
sidebar_position: 14
---

# Mapper Functions

MicroOrm provides several predefined mapper functions that can be used to control how data is processed when interacting
with the database. These functions are implementations of the `MapperFunctionInterface` and can be assigned to fields
for specific data transformation needs.

## What Are Mapper Functions and Why Are They Important?

Mapper Functions are specialized components in the MicroOrm library that transform data as it moves between your PHP
model objects and the database. They act as intermediaries that apply business rules, type conversions, and format
transformations to ensure data consistency and integrity.

### Key Benefits of Mapper Functions

1. **Automatic Data Transformation**
    - Automatically handle data type conversions between PHP and database systems
    - Format data correctly without manual intervention in each model
    - Maintain consistent representation of specialized data types (like UUIDs, timestamps, etc.)

2. **Separation of Concerns**
    - Isolate data transformation logic from your business logic
    - Keep model classes focused on business rules rather than data conversion details
    - Follow the single responsibility principle for cleaner code organization

3. **Standardized Behavior for Common Fields**
    - Provide consistent patterns for timestamps, primary keys, and other common field types
    - Enable framework-level functionality like soft deletes
    - Simplify maintenance by centralizing data transformation logic

4. **Extensibility**
    - Create custom mapper functions for specific business needs
    - Easily reuse data transformation logic across multiple models
    - Plug into the ORM system transparently without modifying core code

Mapper Functions are a core architectural component of MicroOrm that enables many of its powerful features, from
automatic timestamps to UUID handling and soft deletes.

## Available Mapper Functions

### StandardMapper

The basic mapper function that provides the default behavior for selecting and updating fields. This is the default
mapper function assigned to fields when no specific function is specified.

```php
use ByJG\MicroOrm\MapperFunctions\StandardMapper;
```

### ReadOnlyMapper

Used to create read-only fields that can be retrieved from the database but will not be updated during save operations.

```php
use ByJG\MicroOrm\MapperFunctions\ReadOnlyMapper;
```

This mapper function returns `false` during the update process, which signals to the ORM that this field should not be
included in the update statement.

### NowUtcMapper

Automatically sets the field value to the current UTC date and time. This is particularly useful for timestamp fields
like `created_at` and `updated_at`.

```php
use ByJG\MicroOrm\MapperFunctions\NowUtcMapper;
```

When applied, this function will generate a SQL statement that sets the field to the current timestamp using the
database server's date/time functions.

### UpdateBinaryUuidMapper and SelectBinaryUuidMapper

These mapper functions are used to convert between string UUID values and binary representations for database storage.

```php
use ByJG\MicroOrm\MapperFunctions\UpdateBinaryUuidMapper;
use ByJG\MicroOrm\MapperFunctions\SelectBinaryUuidMapper;
```

- `UpdateBinaryUuidMapper`: Converts a UUID string to a binary representation for storage in the database
- `SelectBinaryUuidMapper`: Converts a binary representation of a UUID back to a string when retrieving from the
  database

## Using Mapper Functions

You can use mapper functions in two ways:

### 1. In Field Attributes

```php
use ByJG\MicroOrm\Attributes\FieldAttribute;
use ByJG\MicroOrm\MapperFunctions\NowUtcMapper;
use ByJG\MicroOrm\MapperFunctions\ReadOnlyMapper;

class MyModel
{
    #[FieldAttribute(
        fieldName: "created_at", 
        updateFunction: ReadOnlyMapper::class,
        insertFunction: NowUtcMapper::class
    )]
    protected ?string $createdAt = null;
}
```

### 2. In FieldMapping

```php
use ByJG\MicroOrm\FieldMapping;
use ByJG\MicroOrm\MapperFunctions\NowUtcMapper;

$fieldMap = FieldMapping::create('created_at')
    ->withInsertFunction(NowUtcMapper::class);

$mapper->addFieldMapping($fieldMap);
```

## Creating Custom Mapper Functions

You can create your own mapper functions by implementing the `MapperFunctionInterface`:

```php
use ByJG\AnyDataset\Db\DbFunctionsInterface;
use ByJG\MicroOrm\Interface\MapperFunctionInterface;

class MyCustomMapper implements MapperFunctionInterface
{
    public function processedValue(mixed $value, mixed $instance, ?DbFunctionsInterface $helper = null): mixed
    {
        // Transform the value as needed
        return $transformedValue;
    }
}
```

The `processedValue` method receives:

- `$value`: The current value of the field
- `$instance`: The entity instance being processed
- `$helper`: The database helper that provides database-specific functions

You can then use your custom mapper function just like the built-in ones:

```php
#[FieldAttribute(fieldName: "myfield", updateFunction: MyCustomMapper::class)]
protected $myField;
```

## How Mapper Functions Work

- **Select**: When data is retrieved from the database, the `selectFunction` is applied to transform the value before
  setting it in the model
- **Update**: When updating a record, the `updateFunction` is applied to prepare the value before sending it to the
  database
- **Insert**: When inserting a new record, the `insertFunction` is applied to prepare the value before sending it to the
  database

If a mapper function returns `false` (like `ReadOnlyMapper` does), the field will be excluded from the database
operation.

## Practical Examples

### Example 1: Automatically Managing Timestamps

A common use case is automatically managing `created_at` and `updated_at` timestamp fields:

```php
use ByJG\MicroOrm\Attributes\FieldAttribute;
use ByJG\MicroOrm\Attributes\TableAttribute;
use ByJG\MicroOrm\MapperFunctions\NowUtcMapper;
use ByJG\MicroOrm\MapperFunctions\ReadOnlyMapper;

#[TableAttribute(tableName: 'users')]
class User
{
    #[FieldAttribute(primaryKey: true)]
    public ?int $id = null;
    
    #[FieldAttribute]
    public ?string $name = null;
    
    #[FieldAttribute(fieldName: "created_at", updateFunction: ReadOnlyMapper::class, insertFunction: NowUtcMapper::class)]
    protected ?string $createdAt = null;
    
    #[FieldAttribute(fieldName: "updated_at", updateFunction: NowUtcMapper::class)]
    protected ?string $updatedAt = null;
    
    // Getters and setters
}
```

In this example:

- `createdAt` is automatically set to the current time when a record is created, and never updated afterward
- `updatedAt` is automatically updated with the current time whenever the record is saved

### Example 2: Handling UUIDs

When working with UUIDs stored as binary in the database:

```php
use ByJG\MicroOrm\Attributes\FieldAttribute;
use ByJG\MicroOrm\MapperFunctions\SelectBinaryUuidMapper;
use ByJG\MicroOrm\MapperFunctions\UpdateBinaryUuidMapper;

class Product
{
    #[FieldAttribute(
        primaryKey: true,
        fieldName: "uuid",
        updateFunction: UpdateBinaryUuidMapper::class,
        selectFunction: SelectBinaryUuidMapper::class
    )]
    protected ?string $uuid = null;
    
    // Other fields and methods
}
```

This setup:

- Converts string UUIDs to binary format when saving to the database
- Converts binary UUIDs back to string format when loading from the database

### Example 3: Custom Data Formatting

For custom data formatting, like storing phone numbers in a standardized format:

```php
use ByJG\AnyDataset\Db\DbFunctionsInterface;
use ByJG\MicroOrm\Attributes\FieldAttribute;
use ByJG\MicroOrm\Interface\MapperFunctionInterface;

class PhoneNumberFormatter implements MapperFunctionInterface
{
    public function processedValue(mixed $value, mixed $instance, ?DbFunctionsInterface $helper = null): mixed
    {
        if (!$value) {
            return $value;
        }
        
        // Strip all non-digit characters for database storage
        return preg_replace('/[^0-9]/', '', $value);
    }
}

class PhoneNumberDisplay implements MapperFunctionInterface
{
    public function processedValue(mixed $value, mixed $instance, ?DbFunctionsInterface $helper = null): mixed
    {
        if (!$value || strlen($value) !== 10) {
            return $value;
        }
        
        // Format as (XXX) XXX-XXXX for display
        return sprintf("(%s) %s-%s", 
            substr($value, 0, 3),
            substr($value, 3, 3),
            substr($value, 6)
        );
    }
}

class Contact
{
    #[FieldAttribute(
        fieldName: "phone",
        updateFunction: PhoneNumberFormatter::class,
        selectFunction: PhoneNumberDisplay::class
    )]
    protected ?string $phoneNumber = null;
    
    // Other fields and methods
}
```

This example:

- Strips all non-digit characters from phone numbers before storing in the database
- Formats phone numbers as (XXX) XXX-XXXX when retrieving from the database

These mapper functions ensure data consistency without cluttering your business logic with formatting code. 