---
sidebar_position: 6
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

`TableUuidPKAttribute` is the table attribute for a table whose primary key is a UUID. On insert it generates a
version 7 UUID (time-ordered) in PHP and writes it in the syntax of the repository's database. It works on MySQL,
SQLite, PostgreSQL and SQL Server.

`TableMySqlUuidPKAttribute`, `TableSqliteUuidPKAttribute` and `TablePgsqlUuidPKAttribute` are deprecated aliases of
`TableUuidPKAttribute`, kept so existing models keep working.

### Using UUID attributes

Here's an example of how to use the UUID attributes:

```php
#[TableUuidPKAttribute(tableName: 'my_table')]
class MyModelWithUuid
{
    #[FieldUuidAttribute(primaryKey: true)]
    public ?string $id = null;

    #[FieldAttribute()]
    public ?string $name = null;
}
```

In this example:

- `TableUuidPKAttribute` generates the UUID for new records
- `FieldUuidAttribute` marks the `$id` field as a UUID primary key

### Field UUID Attribute

The `FieldUuidAttribute` is a specialized field attribute designed for UUID fields. It internally configures the
appropriate mapper functions for handling UUID values:

- For reading from the database: Uses `FormatSelectUuidMapper` to convert the stored UUID to string format
- For writing to the database: Uses `FormatUpdateUuidMapper` to convert the UUID to the literal of the database

### Database Schema Requirements

Define the UUID column as `BINARY(16)`, or as `uuid` on PostgreSQL:

```sql
-- MySQL, SQLite and SQL Server
CREATE TABLE my_table
(
    id   BINARY(16) PRIMARY KEY,
    name VARCHAR(45)
);

-- PostgreSQL
CREATE TABLE my_table
(
    id   UUID PRIMARY KEY,
    name VARCHAR(45)
);
```

### UUID Literals

Each database writes a UUID its own way: `HexUuidLiteral` (`X'...'`, MySQL and SQLite), `SqlServerUuidLiteral`
(`0x...`) and `PostgresUuidLiteral` (`'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX'`). `HexUuidLiteral::forDatabase()`
returns the right one for an executor. See [UUID Support](uuid-support.md).


