# MicroOrm for PHP

[![Build Status](https://github.com/byjg/php-micro-orm/actions/workflows/phpunit.yml/badge.svg?branch=master)](https://github.com/byjg/php-micro-orm/actions/workflows/phpunit.yml)
[![Opensource ByJG](https://img.shields.io/badge/opensource-byjg-success.svg)](http://opensource.byjg.com)
[![GitHub source](https://img.shields.io/badge/Github-source-informational?logo=github)](https://github.com/byjg/php-micro-orm/)
[![GitHub license](https://img.shields.io/github/license/byjg/php-micro-orm.svg)](https://opensource.byjg.com/opensource/licensing.html)
[![GitHub release](https://img.shields.io/github/release/byjg/php-micro-orm.svg)](https://github.com/byjg/php-micro-orm/releases/)

A micro framework for create a very simple decoupled ORM.
This library intended to be very small and very simple to use;

Key Features:

* Can be used with any DTO, Entity, Model or whatever class with public properties or with getter and setter
* The repository support a variety of datasources: MySql, Sqlite, Postgres, MySQL, Oracle (see byjg/anydataset)
* A class Mapper is used for mapping the Entity and the repository
* Powerful mapper functions for automatic data transformation between models and database
* Small and simple to use

## Architecture

These are the key components:

```text
┌──────────────────────────┐
│ Repository               │              ┌─────────────────────┐
│                          │         ┌────│        Model        │
│                          │         │    └─────────────────────┘
│          ┌───────────────┴─────┐   │               │
│          │       Mapper        │───┤               │
│          └───────────────┬─────┘   │               │
│                     │    │         │    ┌─────────────────────┐
│                     │    │         └────│    FieldMapping     │
│                     │    │              └─────────────────────┘
│                     │    │
│          ┌───────────────┴─────┐
│          │        Query        │
│          └───────────────┬─────┘
│                          │
│          ┌───────────────┴─────┐
│          │  DbDriverInterface  │───────────────┐
│          └───────────────┬─────┘               │
│                          │                     │
└──────────────────────────┘                .─────────.
                                           │           │
                                           │`─────────'│
                                           │           │
                                           │    DB     │
                                           │           │
                                           │           │
                                            `─────────'
```

* Model can be any class with public properties or with getter and setter. It is used to retrieve or save the data into
  the database
* Mapper defines the relationship between the Model properties and the database fields
* Query defines what to retrieve from/update in the database. It uses the Mapper to prepare the query to the database
  converting the Model properties to database fields.
* DbDriverInterface is the implementation to the Database connection.
* Repository put all this together


## Getting Started

### Table Structure

We have the following table structure in the database for this example:

```sql
CREATE TABLE `mytable` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `company_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

We want to be able to interact with this table using the ORM.

### Defining the Model

A Model in our context is a class that symbolizes the data you wish to store or fetch from the database.
This Model class can be as simple as a class with public properties. 
Alternatively, it can be a class equipped with getter and setter methods for more controlled access and 
manipulation of the data. 

To map the database fields, you can add attributes to the Model class. Each property in the Model class represents a field in the database. 

Let's look at an example:
```php
#[TableAttribute(tableName: 'mytable')]
class MyModel
{
    #[FieldAttribute(primaryKey: true)]
    public ?int $id = null;

    #[FieldAttribute()]
    public ?string $name = null;

    #[FieldAttribute(fieldName: 'company_id')
    public ?int $companyId = null;
}
```

In this example, we have a class `MyModel` with three properties: `id`, `name`, and `companyId`.

* The `id` property is marked as a primary key.
* The `name` property is a simple field.
* The `companyId` property is a field with a different name in the database `company_id`.

The `TableAttribute` is used to define the table name in the database.

### Connecting the repository

After defining the Model, you can connect the Model with the repository.

```php
$dbDriver = \ByJG\AnyDataset\Db\Factory::getDbInstance('mysql://user:password@server/schema');
$repository = new \ByJG\MicroOrm\Repository($dbDriver, MyModel::class);
```

### Querying the database

You can query the database using the repository.

```php
$myModel = $repository->get(1);
```

or

```php
$query = Query::getInstance()
    ->field('name')
    ->where('company_id = :cid', ['cid' => 1]);

$result = $repository->getByQuery($query);
```

or, the same example above:

```php
$filterModel = $repository->entity([
    'company_id' => 1
]);

$query = $repository->queryInstance($filterModel);
$query->field('name');

$result = $repository->getByQuery($query);
```

## Basics

* [Defining the Model](docs/getting-started-model.md)
* [Querying the Database](docs/querying-the-database.md)
* [Updating the database](docs/updating-the-database.md)
* [Using the Mapper Object](docs/using-mapper-object.md)
* [The Model Attributes](docs/model-attribute.md)
* [The Repository Class](docs/repository.md)
* [Common Traits for Timestamp Fields](docs/common-traits.md)

## Advanced Topics

* [Active Record](docs/active-record.md)
* [Auto Discovering Relationship](docs/auto-discovering-relationship.md)
* [The Literal Object](docs/the-literal-object.md)
* [Soft Delete](docs/softdelete.md)
* [Caching the Results](docs/cache.md)
* [Observing the Database](docs/observers.md)
* [Controlling the data queried/updated](docs/controlling-the-data.md)
* [Mapper Functions](docs/mapper-functions.md)
* [Using FieldAlias](docs/using-fieldalias.md)
* [Tables without auto increments fields](docs/tables-without-auto-increment-fields.md)
* [Using With Recursive SQL Command](docs/using-with-recursive-sql-command.md)
* [QueryRaw (raw SQL)](docs/query-raw.md)
* [Update Constraints](docs/update-constraints.md)
* [Building SQL Queries](docs/query-build.md)
* [UUID Support](docs/uuid-support.md)

## Install

Just type:

```bash
composer require "byjg/micro-orm"
```

## Running Tests

```bash
./vendor/bin/phpunit
```

## Related Projects

* [Database Migration](https://github.com/byjg/migration)
* [Anydataset](https://github.com/byjg/anydataset)
* [PHP Rest Template](https://github.com/byjg/php-rest-template)
* [USDocker](https://github.com/usdocker/usdocker)
* [Serializer](https://github.com/byjg/serializer)

## Dependencies

```mermaid
flowchart TD
    byjg/micro-orm --> byjg/anydataset-db
    byjg/micro-orm --> ext-json
```

----
[Open source ByJG](http://opensource.byjg.com)