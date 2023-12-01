# MicroOrm for PHP

[![Build Status](https://github.com/byjg/micro-orm/actions/workflows/phpunit.yml/badge.svg?branch=master)](https://github.com/byjg/micro-orm/actions/workflows/phpunit.yml)
[![Opensource ByJG](https://img.shields.io/badge/opensource-byjg-success.svg)](http://opensource.byjg.com)
[![GitHub source](https://img.shields.io/badge/Github-source-informational?logo=github)](https://github.com/byjg/micro-orm/)
[![GitHub license](https://img.shields.io/github/license/byjg/micro-orm.svg)](https://opensource.byjg.com/opensource/licensing.html)
[![GitHub release](https://img.shields.io/github/release/byjg/micro-orm.svg)](https://github.com/byjg/micro-orm/releases/)

A micro framework for create a very simple decoupled ORM.
This library intended to be very small and very simple to use;

Key Features:

* Can be used with any DTO, Entity, Model or whatever class with public properties or with getter and setter
* The repository support a variety of datasources: MySql, Sqlite, Postgres, MySQL, Oracle (see byjg/anydataset)
* A class Mapper is used for mapping the Entity and the repository
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

* Model is a get/set class to retrieve or save the data into the database
* Mapper will create the definitions to map the Model into the Database.
* Query will use the Mapper to prepare the query to the database based on DbDriverInterface
* DbDriverIntarce is the implementation to the Database connection.
* Repository put all this together

## Examples

For the examples below we will use the class 'Users';

```php
<?php
class Users
{
    public $id;
    public $name;
    public $createdate;
}
```

First of all will create a Table Mapping class:

```php
<?php
// Creating the mapping
$mapper = new \ByJG\MicroOrm\Mapper(
    Users::class,   // The full qualified name of the class
    'users',        // The table that represents this entity
    'id'            // The primary key field
);

// Optionally you can define table mappings between the propoerties
// and the database fields;
// The example below will map the property 'createdate' to the database field 'created';
$mapper->addFieldMapping(FieldMap::create('createdate')->withFieldName('created'));
```

Then you need to create the dataset object and the repository:

```php
<?php
$dataset = \ByJG\AnyDataset\Db\Factory::getDbRelationalInstance('mysql://user:password@server/schema');

$repository = new \ByJG\MicroOrm\Repository($dataset, $mapper);
```

Some examples with the repository:

```php
<?php

// Get a single data from your ID
$users = $repository->get(10);

// Persist the entity into the database:
// Will INSERT if does not exists, and UPDATE if exists
$users->name = "New name";
$repository->save($users);
```

## Advanced uses

Get a collection using the query object:

```php
<?php
$query = \ByJG\MicroOrm\Query::getInstance()
    ->table('users')
    ->fields(['id', 'name'])
    ->where('name like :part', ['part' => 'A%']);

// Will return a collection o 'Users'
$collection = $repository->getByQuery($query);
```

Returning multiples entities with a query:

```php
<?php
$query = \ByJG\MicroOrm\Query::getInstance()
    ->table('order')
    ->join('item', 'order.id = item.orderid')
    ->where('name like :part', ['part' => 'A%']);

// Will return a collection of Orders and Items:
// $collection = [
//     [ $order, $item ],
//     [ $order, $item ],
//     ...
// ];
$collection = $orderRepository->getByQuery(
    $query,
    [
        $itemRepository->getMapper()
    ]
);
```

## Using FieldAlias

Field alias is an alternate name for a field. This is usefull for disambiguation on join and leftjoin queries.
Imagine in the example above if both tables ITEM and ORDER have the same field called 'ID'.

In that scenario, the value of ID will be overriden. The solution is use the FieldAlias like below:

```php
<?php
// Create the Mapper and the proper fieldAlias
$orderMapper  = new \ByJG\MicroOrm\Mapper(...);
$orderMapper->addFieldMapping(FieldMapping::create('id')->withFieldAlias('orderid'));
$itemMapper  = new \ByJG\MicroOrm\Mapper(...);
$itemMapper->addFieldMappping(FieldMapping::create('id')->withFieldAlias('itemid'));

$query = \ByJG\MicroOrm\Query::getInstance()
    ->field('order.id', 'orderid')
    ->field('item.id', 'itemid')
        /* Other fields here */
    ->table('order')
    ->join('item', 'order.id = item.orderid')
    ->where('name like :part', ['part' => 'A%']);

// Will return a collection of Orders and Items:
// $collection = [
//     [ $order, $item ],
//     [ $order, $item ],
//     ...
// ];
$collection = $orderRepository->getByQuery(
    $query,
    [
        $itemRepository->getMapper()
    ]
);
```

You can also add a MAPPER as a Field. In that case the MAPPER will create the field and the correct aliases.

```php
<?php
$query = \ByJG\MicroOrm\Query::getInstance()
    ->fields([
        $orderRepository->getMapper(),
        $itemRepository->getMapper,
    ]);
```

## Tables without auto increments fields

```php
<?php
// Creating the mapping
$mapper = new \ByJG\MicroOrm\Mapper(
    Users::class,   // The full qualified name of the class
    'users',        // The table that represents this entity
    'id',            // The primary key field
    function () {
        // calculate and return the unique ID
    }
);
```

## Applying functions for Select and Update

```php
<?php
// Creating the mapping
$mapper = new \ByJG\MicroOrm\Mapper(...);

$fieldMap = FieldMap::create('propertyname') // The property name of the entity class
    // The field name of the table. if not defined will use the property name.
    ->withFieldName('fieldname')
    // The field alias of the field in the table. if not defined will use the field name.
    ->withFieldAlias('alias')
    // Returns the pre-processed value before UPDATE/INSERT the $field name
    // If the function returns NULL this field will not be included in the UPDATE/INSERT
    ->withUpdateFunction(function ($field, $instance) {
        return $field;
    })
    // Returns the field value with a post-processed value of $field AFTER query from DB
    ->withSelectFunction(function ($field, $instance) {
        return $field;
    })

$mapper->addFieldMapping($fieldMap);
```

## Observers

You can add observers to the repository. 
The observer will be called after the insert, update or delete a record in the DB.

```mermaid
flowchart TD
    A[MyRepository] --> |1. addObserver| B[Subject]
    C[ObserverdRepository] --> |2. Notify Update| B
    B --> |3. Execute Callback| A
```

```php
<?php
// This observer will be called after insert, update or delete a record on the table 'triggerTable' 
$myRepository->addObserver("triggerTable", function ($table, $event, $data, $repository) {
    // Do something here
});
```

The callback will receive the following parameters:
 - `$table`: The table name that was affected
 - `$event`: The event that was triggered. Can be 'insert', 'update' or 'delete'
 - `$data`: The data that was inserted, updated or deleted
 - `$repository`: The repository is listening to the event (the same as $myRepository)

*Note*: The observer will not be called if the insert, update or delete is called using the DBDriver object.

## Using With Recursive SQL Command

```php
<?php
$recursive = \ByJG\MicroOrm\Recursive::getInstance('test')
    ->field('start', 1, 'start + 10')
    ->field('end', 120, "end - 10")
    ->where('start < 100')
;

$query = \ByJG\MicroOrm\Query::getInstance()
    ->withRecursive($recursive)
    ->fields(['start', 'end']);

/*
This will produce the following SQL:

WITH RECURSIVE test(start, end) AS (
    SELECT 1 as start, 120 as end
    UNION ALL SELECT start + 10, end - 10 FROM test WHERE start < 100
) SELECT start, end FROM test
*/
```

## Pre-defined closures for field map

### Mapper::defaultClosure($value, $instance)

Defines the basic behavior for select and update fields;

### Mapper::doNotUpdateClosure($value, $instance)

If set in the update field map will make the field not updatable by the micro-orm.
It is usefull for fields that are pre-defined like 'Primary Key'; timestamp fields based on the
update and the creation; and others

## Before insert and update functions

You can also set closure to be applied before insert or update a record.
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

## TransactionManager object

It allows you to create a single database transaction with multiple repositories.
If any of the repositories fails the transaction will be rolled back for all repositories.
When you commit the transaction all repositories will be commited.

```php
<?php
$repo1 = new Repository(...);
$repo2 = new Repository(...);

// Create the TransactionManager
$transactionManager = new TransactionManager();
$transactionManager->addRepository($repo1);
$transactionManager->addRepository($repo2);

// Start the transaction
$transactionManager->beginTransaction();

//
// Do some Repository operations with the repo;
// ...

// commit (or rollback all transactions)
$transactionManager->commitTransaction();
```

## Install

Just type:

```bash
composer require "byjg/micro-orm=4.0.*"
```

## Running Tests

```bash
vendor/bin/phpunit
```

## Related Projects

* [Database Migration](https://github.com/byjg/migration)
* [Anydataset](https://github.com/byjg/anydataset)
* [PHP Rest Template](https://github.com/byjg/php-rest-template)
* [USDocker](https://github.com/usdocker/usdocker)
* [Serializer](https://github.com/byjg/serializer)

----
[Open source ByJG](http://opensource.byjg.com)
