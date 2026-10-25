---
sidebar_key: micro-orm
sidebar_category: data-access
tags: [php, databases, orm]
---

# MicroOrm for PHP

A small ORM that maps any class to a table, and lets you choose the pattern per entity: Repository, Data Mapper or Active Record.

[![Sponsor](https://img.shields.io/badge/Sponsor-%23ea4aaa?logo=githubsponsors&logoColor=white&labelColor=0d1117)](https://github.com/sponsors/byjg)
[![Build Status](https://github.com/byjg/php-micro-orm/actions/workflows/phpunit.yml/badge.svg?branch=master)](https://github.com/byjg/php-micro-orm/actions/workflows/phpunit.yml)
[![Opensource ByJG](https://img.shields.io/badge/opensource-byjg-success.svg)](https://opensource.byjg.com)
[![Install MCP Server](https://img.shields.io/badge/Install-MCP_Server-8A2BE2?logo=modelcontextprotocol&logoColor=white)](https://opensource.byjg.com/docs/ai/mcpserver-byjg-docs/)
[![GitHub source](https://img.shields.io/badge/Github-source-informational?logo=github)](https://github.com/byjg/php-micro-orm/)
[![GitHub license](https://img.shields.io/github/license/byjg/php-micro-orm.svg)](https://opensource.byjg.com/license/)
[![GitHub release](https://img.shields.io/github/release/byjg/php-micro-orm.svg)](https://github.com/byjg/php-micro-orm/releases/)

## Why

Full ORMs pick the pattern for the whole application: Eloquent is Active Record and your
models extend its base class, Doctrine is Data Mapper with its unit of work. Changing your
mind later means rewriting the models.

MicroORM maps a plain class (public properties, or getters and setters) with attributes or
a separate `Mapper`, and every way of using it shares that mapper:

```php
$repository = new Repository($executor, User::class);   // Repository + Data Mapper
$user = $repository->get(1);

$user = User::get(1);                                   // Active Record (the ActiveRecord trait)
```

- **Choose the pattern per entity.** A simple lookup table can be Active Record while the
  entity with the business rules gets a repository. See [Choosing a Pattern](docs/patterns.md).
- **Observers on every write.** An observer is notified of each insert, update and delete
  on its table, whatever code path made the write.

Key Features:

* Can be used with any DTO, Entity, Model or whatever class with public properties or with getter and setter
* The repository support a variety of datasources: MySql, Sqlite, Postgres, MySQL, Oracle (see byjg/anydataset)
* A class Mapper is used for mapping the Entity and the repository
* Powerful mapper functions for automatic data transformation between models and database
* Small and simple to use

## Architecture

MicroORM implements **Martin Fowler's enterprise patterns**:

- **[Repository](https://martinfowler.com/eaaCatalog/repository.html)**: Mediates between domain and data mapping layers
- **[Data Mapper](https://martinfowler.com/eaaCatalog/dataMapper.html)**: Separates domain objects from database tables
- **[Active Record](https://martinfowler.com/eaaCatalog/activeRecord.html)**: Wraps database rows with domain logic,
  as a facade over the two above

They are layers, not alternatives. The **Mapper** (Data Mapper) and the **Repository** are always there; the
`ActiveRecord` trait is a facade that creates them for the model. What you choose is where the mapping lives (a `Mapper`
object or attributes on the model) and where the persistence calls live (a repository or the model itself). See
[Choosing a Pattern](docs/patterns.md).

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
│                     │    │
│          ┌───────────────┴─────┐        ┌──────────────────────┐  
│          │  DatabaseExecutor   │────────│   DbDriverInterface  │  
│          └───────────────┬─────┘        └────────────┬─────────┘  
│                          │                           │                
└──────────────────────────┘                      .─────────.           
                                                 │           │          
                                                 │`─────────'│          
                                                 │           │          
                                                 │    DB     │          
                                                 │           │          
                                                 │           │          
                                                  `─────────'           
```

* **Model** can be any class with public properties or with getter and setter. It is used to retrieve or save the data
  into the database
* **Mapper** defines the relationship between the Model properties and the database fields
* **FieldMapping** defines individual field mappings within the Mapper (field names, transformations, relationships via
  `parentTable`)
* **Query** defines what to retrieve from/update in the database. It uses the Mapper to prepare the query to the
  database converting the Model properties to database fields
* **DatabaseExecutor** (external package) wraps the DbDriver and provides transaction management, query execution, and
  access to database helpers
* **DbDriverInterface** (external package) is the actual database driver implementation that connects to the database
* **Repository** orchestrates all MicroORM components and uses DatabaseExecutor to interact with the database

For a detailed explanation of the architecture and when to use each layer,
see [Architecture Layers: Infrastructure vs Domain](docs/architecture-layers.md).


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

    #[FieldAttribute(fieldName: 'company_id')]
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
$executor = \ByJG\AnyDataset\Db\DatabaseExecutor::using($dbDriver);
$repository = new \ByJG\MicroOrm\Repository($executor, MyModel::class);
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
* [Choosing a Pattern](docs/patterns.md)
* [Querying the Database](docs/querying-the-database.md)
* [Updating the database](docs/updating-the-database.md)
* [Using the Mapper Object](docs/using-mapper-object.md)
* [The Model Attributes](docs/model-attribute.md)
* [The Repository Class](docs/repository.md)
* [Common Traits for Timestamp Fields](docs/common-traits.md)
* [Architecture Layers: Infrastructure vs Domain](docs/architecture-layers.md)
* [Comparison with Other ORMs (Eloquent, Doctrine)](docs/comparison-with-other-orms.md)

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

## Related Projects

* [Database Migration](https://github.com/byjg/migration)
* [Anydataset](https://github.com/byjg/anydataset)
* [PHP Rest Template](https://github.com/byjg/php-rest-template)
* [USDocker](https://github.com/usdocker/usdocker)
* [Serializer](https://github.com/byjg/serializer)

## Installation

Just type:

```bash
composer require "byjg/micro-orm"
```

## Running Tests

```bash
./vendor/bin/phpunit
```

----
[Open source ByJG](https://opensource.byjg.com)