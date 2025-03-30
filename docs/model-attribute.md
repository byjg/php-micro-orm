---
sidebar_position: 5
---

# The Model Attributes

The Model Attributes are used to define the table structure in the database. 

The attributes are:

* `TableAttribute`: Used in the class level. Define that the Model is referencing a table in the database.
* `FieldAttribute`: Define the properties in the class that are fields in the database.

## Example

```php
#[TableAttribute(tableName: 'mytable')]
class MyModel
{
    #[FieldAttribute(primaryKey: true)]
    public ?int $id = null;

    #[FieldAttribute()]
    public ?string $name = null;

    #[FieldAttribute(fieldName: 'company_id')]
    public ?int $companyId = null;
    
    #[FieldAttribute(fieldName: 'created_at')]
    protected ?string $createdAt;
    
    #[FieldAttribute(fieldName: 'updated_at')]
    protected ?string $updatedAt;
    
    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }
    
    public function setCreatedAt(?string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
    
    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }
    
    public function setUpdatedAt(?string $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
```

In this example, we have a class `MyModel` with five properties: `id`, `name`, `companyId`, `createdAt`, and `updatedAt`.

* The `id` property is marked as a primary key.
* The `name` property is a simple field.
* The `companyId` property is a field with a different name in the database `company_id`.
* The same for `createdAt` and `updatedAt`. These properties are fields with a different name in the database
  `created_at` and `updated_at`.

Note: Specically for the `createdAt` and `updatedAt` properties, we have a getter and setter.
See [common traits](common-traits.md) for more information.

The `TableAttribute` is used to define the table name in the database.

## Where to use FieldAttribute

Please read [Getting Started with Model Attributes for more information](getting-started-model.md).


## Table Attributes parameters

The `TableAttribute` has the following parameters:

| Field                      | Description                                                                                                                                    | Required |
|----------------------------|------------------------------------------------------------------------------------------------------------------------------------------------|:--------:|
| **tableName**              | The name of the table in the database.                                                                                                         |   Yes    |
| **primaryKeySeedFunction** | A function that returns the seed for the primary key. The function must return a value.                                                        |    No    |
| **tableAlias**             | The alias of the table in the database.                                                                                                        |    No    |
| **beforeInsert**           | A processor that is called before inserting a record into the database. Can be a string class name or an instance of EntityProcessorInterface. |    No    |
| **beforeUpdate**           | A processor that is called before updating a record in the database. Can be a string class name or an instance of EntityProcessorInterface.    |    No    |

## Field Attributes parameters

The `FieldAttribute` has the following parameters:

| Field              | Description                                                                                                                                      | Required |
|--------------------|--------------------------------------------------------------------------------------------------------------------------------------------------|:--------:|
| **primaryKey**     | If the field is a primary key. It is required at least one field to be a PK.  It is used for insert/updates/deletes.                             |    No    |
| **fieldName**      | The name of the field in the database. If not set, the field name is the same as the property name.                                              |    No    |
| **fieldAlias**     | The alias of the field in the database. If not set, the field alias is the same as the field name.                                               |    No    |
| **syncWithDb**     | If the field should be synchronized with the database. Default is true.                                                                          |    No    |
| **updateFunction** | A function that is called when the field is updated. The function must return a value.                                                           |    No    |
| **selectFunction** | A function that is called when the field is selected. The function must return a value.                                                          |    No    |
| **insertFunction** | A function that is called when the field is inserted. The function must return a value.                                                          |    No    |
| **parentTable**    | The parent table of the field. This is used in the case of a foreign key. See [Auto Discovering Relationship](auto-discovering-relationship.md). |    No    |

See also [Controlling the Data](controlling-the-data.md) for more information about the `updateFunction`,
`selectFunction`, and `insertFunction`.

## Special Table Attributes for UUID primary keys

MicroOrm provides specialized table attributes for tables with UUID primary keys:

* `TableUuidPKAttribute` - Base class for UUID primary key tables (generic implementation)
* `TableMySqlUuidPKAttribute` - Specific implementation for MySQL databases with UUID primary keys
* `TableSqliteUuidPKAttribute` - Specific implementation for SQLite databases with UUID primary keys

These attributes automatically configure the primary key generation for UUID fields. They handle the creation of binary
UUID values in a format appropriate for the specific database engine.

### Using UUID attributes

Here's an example of how to use the UUID attributes:

```php
#[TableSqliteUuidPKAttribute(tableName: 'my_table')]
class MyModelWithUuid
{
    #[FieldUuidAttribute(primaryKey: true)]
    public ?string $id = null;

    #[FieldAttribute()]
    public ?string $name = null;
}
```

In this example:

- `TableSqliteUuidPKAttribute` sets up the model to work with SQLite's UUID handling
- `FieldUuidAttribute` marks the `$id` field as a UUID primary key
- The library will automatically generate UUID values for new records

### Field UUID Attribute

The `FieldUuidAttribute` is a specialized field attribute designed for UUID fields. It internally configures the
appropriate mapper functions for handling binary UUID values:

- For reading from the database: Uses `SelectBinaryUuidMapper` to convert binary UUID to string format
- For writing to the database: Uses `UpdateBinaryUuidMapper` to convert string UUID to binary format

### Database Schema Requirements

To use UUID primary keys, you need to define your table with a binary field for the UUID. For example:

```sql
-- SQLite example
CREATE TABLE my_table
(
    id   BINARY(16) PRIMARY KEY,
    name VARCHAR(45)
);

-- MySQL example
CREATE TABLE my_table
(
    id   BINARY(16) PRIMARY KEY,
    name VARCHAR(45)
);
```

### UUID Literals

The library provides specialized literal classes for handling UUID values in different database engines:

- `HexUuidLiteral` - Base class for UUID literals
- `MySqlUuidLiteral` - MySQL-specific UUID literal
- `SqliteUuidLiteral` - SQLite-specific UUID literal
- `PostgresUuidLiteral` - PostgreSQL-specific UUID literal

These literals help ensure the UUID values are properly formatted for each database engine.


