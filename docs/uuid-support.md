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
- Database-specific UUID syntax (MySQL, SQLite, PostgreSQL, SQL Server)
- Automatic formatting when reading from and writing to the database
- Support for UUID fields beyond just primary keys

## Setting Up UUID Primary Keys

### 1. Database Schema

Store the UUID as 16 bytes, or in PostgreSQL's native `uuid` type:

```sql
-- MySQL and SQLite
CREATE TABLE my_table (
    id BINARY(16) PRIMARY KEY,  -- Binary format is more efficient than VARCHAR
    name VARCHAR(100)
);

-- SQL Server
CREATE TABLE my_table (
    id BINARY(16) PRIMARY KEY,
    name VARCHAR(100)
);

-- PostgreSQL
CREATE TABLE my_table (
    id UUID PRIMARY KEY,
    name VARCHAR(100)
);
```

On SQL Server use `BINARY(16)` rather than `UNIQUEIDENTIFIER`: SQL Server reorders the first 8 bytes of a
`UNIQUEIDENTIFIER`, so the value read back would not be the one written.

### 2. Model Definition

Then define your model with `TableUuidPKAttribute` and `FieldUuidAttribute`. The same model works on every database:

```php
<?php

namespace MyApp\Model;

use ByJG\MicroOrm\Attributes\FieldAttribute;
use ByJG\MicroOrm\Attributes\FieldUuidAttribute;
use ByJG\MicroOrm\Attributes\TableUuidPKAttribute;

#[TableUuidPKAttribute(tableName: 'my_table')]
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

After `save()`, `getId()` returns the new UUID as a formatted string. A UUID set on the model before the first
`save()` is kept: a key is only generated when it is empty.

## Available UUID Attributes

### Table Attribute

`TableUuidPKAttribute` generates the primary key in PHP when a row is inserted, with no database round trip:

- It is a **version 7** UUID (RFC 9562): the first 48 bits are the time in milliseconds and the rest is random, so a
  key created later sorts after the earlier ones and new rows go to the end of the primary key index.
- It is written in the syntax of the database the repository uses (see [UUID literals](#working-with-uuid-literals)).

`TableMySqlUuidPKAttribute`, `TableSqliteUuidPKAttribute` and `TablePgsqlUuidPKAttribute` are **deprecated**. They
are now the same as `TableUuidPKAttribute` and only remain so existing models keep working. (The MySQL one used to ask
MySQL for `uuid_to_bin(uuid())`, which failed on any other database.)

### Field Attribute

The `FieldUuidAttribute` is used to mark UUID fields (both primary keys and regular fields). It internally configures:

- `FormatUpdateUuidMapper` - Converts the UUID to the literal of the database when saving (a string, or a
  `HexUuidLiteral` made without knowing the database, e.g. in a setter)
- `FormatSelectUuidMapper` - Converts the value read from the database to a formatted string (with dashes)

These mapper functions ensure that:

- Your application code works with human-readable UUID strings (e.g., `'550e8400-e29b-41d4-a716-446655440000'`)
- The database stores UUIDs efficiently as 16-byte binary values (or as `uuid` on PostgreSQL)
- Conversions happen automatically during save and retrieve operations

## Generating a UUID

`HexUuidLiteral::v7()` returns a new version 7 UUID as a formatted string, the same value `TableUuidPKAttribute`
generates:

```php
use ByJG\MicroOrm\Literal\HexUuidLiteral;

$id = HexUuidLiteral::v7(); // e.g. '01A141AF-F7F3-7498-B30C-E7C6C3D82E68'
```

A version 7 UUID tells when it was created. For a secret, such as a password-reset token, use a random (version 4)
UUID instead.

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

When using manual queries with `where()` clauses, you **must** pass the UUID as a literal, in the syntax of the
database. `HexUuidLiteral::forDatabase()` picks it from the executor:

```php
use ByJG\MicroOrm\Literal\HexUuidLiteral;

$uuid = '550e8400-e29b-41d4-a716-446655440000';
$executor = $repository->getExecutor();

// ✓ CORRECT - The literal for the repository's database
$query = $repository->getMapper()->getQuery();
$query->where('id = :id', ['id' => HexUuidLiteral::forDatabase($executor, $uuid)]);
$results = $repository->getByQuery($query);

// ✓ Also correct for non-primary key UUID fields
$query->where('user_id = :user_id', ['user_id' => HexUuidLiteral::forDatabase($executor, $userId)]);

// ✗ INCORRECT - This won't work correctly
$query->where('id = :id', ['id' => $uuid]); // String won't match binary in DB
```

### Why the Difference?

- **`get()` / `delete()`**: These methods know they're working with primary keys, so they automatically apply the
  `updateFunction` from the field mapping
- **`where()` clauses**: These are generic and don't know which fields are UUIDs, so you must explicitly wrap UUID
  values in the appropriate literal class

## Working with UUID Literals

A UUID literal is written into the SQL as it is. Each database has its own syntax:

| Class                  | Written as                                 | Database / column             |
|------------------------|--------------------------------------------|-------------------------------|
| `HexUuidLiteral`       | `X'550E8400E29B41D4A716446655440000'`      | MySQL, SQLite: `BINARY(16)`   |
| `SqlServerUuidLiteral` | `0x550E8400E29B41D4A716446655440000`       | SQL Server: `BINARY(16)`      |
| `PostgresUuidLiteral`  | `'550E8400-E29B-41D4-A716-446655440000'`   | PostgreSQL: `uuid`            |

`MySqlUuidLiteral` and `SqliteUuidLiteral` are the same as `HexUuidLiteral`.

`HexUuidLiteral::forDatabase($executor, $uuid)` returns the right one for the executor's database (and
`HexUuidLiteral` when the executor is `null`). Every class accepts a UUID in any of the formats listed under
[Supported Input Formats](#supported-input-formats), or another UUID literal.

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

### MySQL and SQLite

Neither has a native UUID type, so UUIDs are stored as `BINARY(16)` and written as `X'...'`.

### PostgreSQL

PostgreSQL has a native `uuid` type. The UUID is written as its quoted text form, which PostgreSQL converts to `uuid`,
and read back as text.

### SQL Server

UUIDs are stored as `BINARY(16)` and written as `0x...`. Both `pdo_dblib` and `pdo_sqlsrv` are supported.

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
echo $order->getId(); // e.g., '01A141AF-F7F3-7498-B30C-E7C6C3D82E68'

// Query by UUID
$found = $repository->get($order->getId());
echo $found->getUserId(); // 'A1B2C3D4-E5F6-7890-ABCD-EF1234567890'

// All UUIDs are automatically formatted when retrieved
``` 