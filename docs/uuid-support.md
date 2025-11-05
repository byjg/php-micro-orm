---
sidebar_position: 13
---

# UUID Support

MicroOrm provides comprehensive support for working with UUID fields across different database systems. This guide
explains how to set up and use UUIDs as primary keys or regular fields in your models.

## Overview

UUIDs (Universally Unique Identifiers) are 128-bit values typically represented as 32 hexadecimal characters. When used
as primary keys, they offer advantages like:

- Globally unique identifiers without coordination between database servers
- No need for a centralized sequence or auto-increment field
- Improved security by avoiding sequential predictable IDs
- Distributed system-friendly identification

MicroOrm handles the complexities of working with UUIDs, including:

- Converting between string UUID representations and binary database storage
- Generating new UUIDs for primary keys automatically
- Database-specific UUID handling (MySQL, PostgreSQL, SQLite)
- Automatic formatting when reading from and writing to the database
- Support for UUID fields beyond just primary keys

## Setting Up UUID Primary Keys

### 1. Database Schema

First, set up your database table with a binary field to store the UUID:

```sql
-- MySQL example
CREATE TABLE my_table (
    id BINARY(16) PRIMARY KEY,  -- Binary format is more efficient than VARCHAR
    name VARCHAR(100),
    -- other fields
);

-- SQLite example
CREATE TABLE my_table (
    id BINARY(16) PRIMARY KEY,
    name VARCHAR(100),
    -- other fields
);

-- PostgreSQL example (can use native UUID type)
CREATE TABLE my_table (
    id UUID PRIMARY KEY,
    name VARCHAR(100),
    -- other fields
);
```

### 2. Model Definition

Then define your model using the appropriate UUID attributes:

```php
<?php

namespace MyApp\Model;

use ByJG\MicroOrm\Attributes\FieldUuidAttribute;
use ByJG\MicroOrm\Attributes\FieldAttribute;

// Use the database-specific UUID table attribute
use ByJG\MicroOrm\Attributes\TableMySqlUuidPKAttribute;  // For MySQL
// OR
use ByJG\MicroOrm\Attributes\TableSqliteUuidPKAttribute; // For SQLite
// OR
use ByJG\MicroOrm\Attributes\TableUuidPKAttribute;       // Generic implementation

#[TableMySqlUuidPKAttribute(tableName: 'my_table')]
class MyModel
{
    #[FieldUuidAttribute(primaryKey: true)]
    protected ?string $id = null;

    #[FieldAttribute()]
    protected ?string $name = null;

    // Getters and setters
    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }
}
```

## Available UUID Attributes

### Table Attributes

MicroOrm provides several table attributes for UUID primary keys:

1. `TableUuidPKAttribute` - Base class for UUID tables (uses generic implementation)
2. `TableMySqlUuidPKAttribute` - MySQL-specific implementation (uses MySQL's UUID functions)
3. `TableSqliteUuidPKAttribute` - SQLite-specific implementation

Each attribute automatically configures a primary key seed function that generates appropriate UUID values for the
specific database.

### Field Attribute

The `FieldUuidAttribute` is used to mark UUID fields (both primary keys and regular fields). It internally configures:

- `FormatUpdateUuidMapper` - Handles converting string UUIDs to `HexUuidLiteral` (binary format) when saving to database
- `FormatSelectUuidMapper` - Handles converting binary UUIDs from database to formatted string (with dashes) when
  reading

These mapper functions ensure that:

- Your application code works with human-readable UUID strings (e.g., `'550e8400-e29b-41d4-a716-446655440000'`)
- The database stores UUIDs efficiently as 16-byte binary values
- Conversions happen automatically during save and retrieve operations

## Using UUID Fields Beyond Primary Keys

You can use `FieldUuidAttribute` on any UUID field, not just primary keys:

```php
use ByJG\MicroOrm\Attributes\FieldAttribute;
use ByJG\MicroOrm\Attributes\FieldUuidAttribute;
use ByJG\MicroOrm\Attributes\TableAttribute;

#[TableAttribute(tableName: 'users')]
class User
{
    #[FieldAttribute(primaryKey: true)]
    protected int $id;

    #[FieldUuidAttribute(fieldName: 'external_id')]
    protected ?string $externalId = null;

    #[FieldUuidAttribute(fieldName: 'tenant_id')]
    protected ?string $tenantId = null;

    #[FieldAttribute]
    protected ?string $name = null;

    // Getters and setters work with string UUIDs
    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(?string $externalId): void
    {
        $this->externalId = $externalId;
    }
}

// Usage
$user = new User();
$user->setExternalId('550e8400-e29b-41d4-a716-446655440000'); // String format
$repository->save($user); // Automatically converted to binary for storage

// When retrieved, automatically converted back to string
$retrieved = $repository->get($user->getId());
echo $retrieved->getExternalId(); // '550E8400-E29B-41D4-A716-446655440000'
```

## Querying by UUID

### Primary Key Queries

When querying by UUID primary keys using `get()` or `delete()`, the conversion is **automatic**:

```php
use ByJG\MicroOrm\Repository;

$repository = new Repository($dbDriver, MyModel::class);
$uuid = '550e8400-e29b-41d4-a716-446655440000';

// For UUID primary keys, just pass the string directly - NO need to wrap in HexUuidLiteral
$entity = $repository->get($uuid); // ✓ Automatically converted via updateFunction

// Delete also works automatically
$repository->delete($uuid); // ✓ Automatically converted via updateFunction
```

The repository automatically applies the `updateFunction` (in this case `FormatUpdateUuidMapper`) to convert the string
UUID to a `HexUuidLiteral` before querying.

### Manual Queries with WHERE Clauses

When using manual queries with `where()` clauses, you **must** explicitly use UUID literals:

```php
use ByJG\MicroOrm\Literal\HexUuidLiteral;

$uuid = '550e8400-e29b-41d4-a716-446655440000';

// ✓ CORRECT - Wrap in HexUuidLiteral for WHERE clauses
$query = $repository->getMapper()->getQuery();
$query->where('id = :id', ['id' => new HexUuidLiteral($uuid)]);
$results = $repository->getByQuery($query);

// ✓ Also correct for non-primary key UUID fields
$query->where('user_id = :user_id', ['user_id' => new HexUuidLiteral($userId)]);

// ✗ INCORRECT - This won't work correctly
$query->where('id = :id', ['id' => $uuid]); // String won't match binary in DB
```

### Why the Difference?

- **`get()` / `delete()`**: These methods know they're working with primary keys, so they automatically apply the
  `updateFunction` from the field mapping
- **`where()` clauses**: These are generic and don't know which fields are UUIDs, so you must explicitly wrap UUID
  values in the appropriate literal class

### Database-Specific Literals

You can use database-specific literal classes if needed:

```php
use ByJG\MicroOrm\Literal\MySqlUuidLiteral;
use ByJG\MicroOrm\Literal\SqliteUuidLiteral;
use ByJG\MicroOrm\Literal\PostgresUuidLiteral;

// MySQL - uses UUID_TO_BIN()
$query->where('id = :id', ['id' => new MySqlUuidLiteral($uuid)]);

// PostgreSQL - uses ::uuid cast
$query->where('id = :id', ['id' => new PostgresUuidLiteral($uuid)]);

// SQLite/Generic - uses hex format
$query->where('id = :id', ['id' => new HexUuidLiteral($uuid)]);
```

## Working with UUID Literals

UUID Literal classes help you work with UUIDs in raw SQL queries. Each database has its own literal format:

- `HexUuidLiteral` - Generic hexadecimal format: `X'550E8400E29B41D4A716446655440000'`
- `MySqlUuidLiteral` - MySQL-specific: `UUID_TO_BIN('550e8400-e29b-41d4-a716-446655440000')`
- `PostgresUuidLiteral` - PostgreSQL: `'550e8400-e29b-41d4-a716-446655440000'::uuid`
- `SqliteUuidLiteral` - SQLite hexadecimal format

### Example Usage

```php
use ByJG\MicroOrm\Literal\HexUuidLiteral;

// Create a literal from a UUID string
$uuid = '550e8400-e29b-41d4-a716-446655440000';
$literal = new HexUuidLiteral($uuid);

// Use in queries
$query->where('id = :id', ['id' => $literal]);

// The literal will be converted to the appropriate format for your database
// e.g., X'550E8400E29B41D4A716446655440000' for direct SQL
```

## Format Helpers

The `HexUuidLiteral` class provides helper methods for formatting and validating UUIDs:

```php
use ByJG\MicroOrm\Literal\HexUuidLiteral;

// Format a binary UUID (16 bytes) to a formatted string
$binaryUuid = hex2bin('550E8400E29B41D4A716446655440000');
$formatted = HexUuidLiteral::getFormattedUuid($binaryUuid);
// Returns: '550E8400-E29B-41D4-A716-446655440000'

// Format a hex string (32 chars) to formatted UUID
$hexString = '550E8400E29B41D4A716446655440000';
$formatted = HexUuidLiteral::getFormattedUuid($hexString);
// Returns: '550E8400-E29B-41D4-A716-446655440000'

// Already formatted UUIDs are returned as-is
$alreadyFormatted = '550E8400-E29B-41D4-A716-446655440000';
$result = HexUuidLiteral::getFormattedUuid($alreadyFormatted);
// Returns: '550E8400-E29B-41D4-A716-446655440000'

// Handle invalid UUIDs gracefully
$invalid = 'not-a-uuid';
$result = HexUuidLiteral::getFormattedUuid($invalid, throwErrorIfInvalid: false);
// Returns: null

// Or with a default value
$result = HexUuidLiteral::getFormattedUuid($invalid, throwErrorIfInvalid: false, default: 'N/A');
// Returns: 'N/A'

// Extract UUID from a literal object
$literal = new HexUuidLiteral('550E8400-E29B-41D4-A716-446655440000');
$uuid = HexUuidLiteral::getUuidFromLiteral($literal);
// Returns: '550E8400-E29B-41D4-A716-446655440000'
```

### Supported Input Formats

`HexUuidLiteral::getFormattedUuid()` can handle various UUID formats:

- **Binary (16 bytes)**: `hex2bin('550E8400E29B41D4A716446655440000')`
- **Hex string (32 chars)**: `'550E8400E29B41D4A716446655440000'`
- **Formatted (36 chars with dashes)**: `'550E8400-E29B-41D4-A716-446655440000'`
- **Literal format**: `X'550E8400E29B41D4A716446655440000'`
- **Prefixed hex**: `0x550E8400E29B41D4A716446655440000`

All formats are automatically detected and converted to the standard formatted string with dashes.

## Database-Specific Notes

### MySQL

MySQL doesn't have a native UUID type, so UUIDs are stored as BINARY(16). The `TableMySqlUuidPKAttribute` uses MySQL's
`UUID_TO_BIN()` and `BIN_TO_UUID()` functions.

### SQLite

SQLite also doesn't have a native UUID type. The `TableSqliteUuidPKAttribute` uses SQLite's `randomblob()` function to
generate UUIDs.

### PostgreSQL

PostgreSQL has a native UUID type. The library provides support through the `PostgresUuidLiteral` class.

## Performance Considerations

Storing UUIDs as BINARY(16) is more efficient than VARCHAR(36) both in terms of storage and indexing performance:

- **Storage**: BINARY(16) uses 16 bytes vs VARCHAR(36) which uses 36 bytes
- **Indexing**: Binary indexes are smaller and faster to traverse
- **Comparison**: Binary comparisons are faster than string comparisons

MicroOrm handles all conversions between string representations and binary storage automatically through the mapper
functions.

## Advanced: Custom UUID Mapper Functions

If you need custom UUID handling, you can create your own mapper functions:

```php
use ByJG\MicroOrm\Interface\MapperFunctionInterface;
use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\MicroOrm\Literal\HexUuidLiteral;

class CustomUuidUpdateMapper implements MapperFunctionInterface
{
    public function processedValue(mixed $value, mixed $instance, ?DatabaseExecutor $executor = null): mixed
    {
        if (empty($value)) {
            return null;
        }

        // Your custom logic here
        // Convert string UUID to your preferred format
        return new HexUuidLiteral($value);
    }
}

class CustomUuidSelectMapper implements MapperFunctionInterface
{
    public function processedValue(mixed $value, mixed $instance, ?DatabaseExecutor $executor = null): mixed
    {
        if (empty($value)) {
            return $value;
        }

        // Your custom logic here
        // Convert binary UUID from database to your preferred format
        return HexUuidLiteral::getFormattedUuid($value, throwErrorIfInvalid: false);
    }
}

// Use in your model
#[FieldAttribute(
    fieldName: 'custom_uuid',
    updateFunction: CustomUuidUpdateMapper::class,
    selectFunction: CustomUuidSelectMapper::class
)]
protected ?string $customUuid = null;
```

## Complete Example

Here's a complete example showing a table with multiple UUID fields:

```php
use ByJG\MicroOrm\Attributes\FieldAttribute;
use ByJG\MicroOrm\Attributes\FieldUuidAttribute;
use ByJG\MicroOrm\Attributes\TableUuidPKAttribute;
use ByJG\MicroOrm\Repository;

#[TableUuidPKAttribute(tableName: 'orders')]
class Order
{
    #[FieldUuidAttribute(primaryKey: true)]
    protected ?string $id = null;

    #[FieldUuidAttribute(fieldName: 'user_id')]
    protected ?string $userId = null;

    #[FieldUuidAttribute(fieldName: 'session_id')]
    protected ?string $sessionId = null;

    #[FieldAttribute]
    protected ?string $status = null;

    #[FieldAttribute(fieldName: 'created_at')]
    protected ?string $createdAt = null;

    // Getters and setters...
}

// Database schema
/*
CREATE TABLE orders (
    id BINARY(16) PRIMARY KEY,
    user_id BINARY(16) NOT NULL,
    session_id BINARY(16),
    status VARCHAR(50),
    created_at DATETIME
);
*/

// Usage
$repository = new Repository($dbDriver, Order::class);

// Create new order
$order = new Order();
$order->setUserId('a1b2c3d4-e5f6-7890-abcd-ef1234567890');
$order->setSessionId('11111111-2222-3333-4444-555555555555');
$order->setStatus('pending');
$repository->save($order);

// ID is automatically generated
echo $order->getId(); // e.g., '3B4E18A0-C719-4ED0-C9B2-026A91889DC0'

// Query by UUID
$found = $repository->get($order->getId());
echo $found->getUserId(); // 'A1B2C3D4-E5F6-7890-ABCD-EF1234567890'

// All UUIDs are automatically formatted when retrieved
``` 