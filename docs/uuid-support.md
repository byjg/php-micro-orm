---
sidebar_position: 13
---

# UUID Support

MicroOrm provides comprehensive support for working with UUID primary keys across different database systems. This guide
explains how to set up and use UUID fields in your models.

## Overview

UUIDs (Universally Unique Identifiers) are 128-bit values typically represented as 32 hexadecimal characters. When used
as primary keys, they offer advantages like:

- Globally unique identifiers without coordination between database servers
- No need for a centralized sequence or auto-increment field
- Improved security by avoiding sequential predictable IDs

MicroOrm handles the complexities of working with UUIDs, including:

- Converting between string UUID representations and binary database storage
- Generating new UUIDs for primary keys
- Database-specific UUID handling

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

The `FieldUuidAttribute` is used to mark fields as UUID primary keys. It internally configures:

- `UpdateBinaryUuidMapper` - Handles converting string UUIDs to binary format when saving to database
- `SelectBinaryUuidMapper` - Handles converting binary UUIDs from database to string format when reading

## Working with UUID Literals

MicroOrm provides literal classes for working with UUIDs in SQL queries:

```php
use ByJG\MicroOrm\Literal\HexUuidLiteral;
use ByJG\MicroOrm\Literal\MySqlUuidLiteral;
use ByJG\MicroOrm\Literal\SqliteUuidLiteral;
use ByJG\MicroOrm\Literal\PostgresUuidLiteral;

// Create a repository
$repository = new Repository($dbDriver, MyModel::class);

// Query by UUID
$uuid = '550e8400-e29b-41d4-a716-446655440000';

// For MySQL
$query = $repository->getMapper()->getQuery();
$query->where('id = :id', ['id' => new MySqlUuidLiteral($uuid)]);

// For SQLite
$query = $repository->getMapper()->getQuery();
$query->where('id = :id', ['id' => new SqliteUuidLiteral($uuid)]);

$result = $repository->getByQuery($query);
```

## Format Helpers

To work with UUID values, you can use the provided format helpers:

```php
use ByJG\MicroOrm\Literal\HexUuidLiteral;

// Format a binary UUID to a string
$uuidString = HexUuidLiteral::getFormattedUuid($binaryUuid, throwErrorIfInvalid: true);

// Or validate a UUID string
if (HexUuidLiteral::isValid($uuidString)) {
    // UUID is valid
}
```

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

Storing UUIDs as BINARY(16) is more efficient than VARCHAR(36) both in terms of storage and indexing performance.
MicroOrm handles the conversion between string representations and binary storage automatically. 