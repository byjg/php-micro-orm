# MicroOrm for PHP

[![Build Status](https://github.com/byjg/micro-orm/actions/workflows/phpunit.yml/badge.svg?branch=master)](https://github.com/byjg/micro-orm/actions/workflows/phpunit.yml)
[![Opensource ByJG](https://img.shields.io/badge/opensource-byjg-success.svg)](http://opensource.byjg.com)
[![GitHub source](https://img.shields.io/badge/Github-source-informational?logo=github)](https://github.com/byjg/micro-orm/)
[![GitHub license](https://img.shields.io/github/license/byjg/micro-orm.svg)](https://opensource.byjg.com/opensource/licensing.html)
[![GitHub release](https://img.shields.io/github/release/byjg/micro-orm.svg)](https://github.com/byjg/micro-orm/releases/)


A micro framework for create a very simple decoupled ORM.
This library intended to be very small and very simple to use;

**Key Features**

* Can be used with any DTO, Entity, Model or whatever class with public properties or with getter and setter
* The repository support a variety of datasources: MySql, Sqlite, Postgres, MySQL, Oracle (see byjg/anydataset)
* A class Mapper is used for mapping the Entity and the repository
* Small and simple to use

## Architecture

These are the key components:

```text
┌─────────────────────────────┐                                
│  Repository                 │                                
│                             │                                
│                             │                                
│                             │                                
│                             │                                
│             ┌───────────────┴───┐       ┌───────────────────┐
│             │      Mapper       │───────│       Model       │
│             └───────────────┬───┘       └───────────────────┘
│                             │                                
│             ┌───────────────┴───┐                            
│             │       Query       │                            
│             └───────────────┬───┘                            
│                             │                                
│             ┌───────────────┴───┐                            
│             │ DbDriverInterface │                            
│             └───────────────┬───┘                            
│                             │                                
└─────────────────────────────┘                                
```

- Model is a get/set class to retrieve or save the data into the database
- Mapper will create the definitions to map the Model into the Database. 
- Query will use the Mapper to prepare the query to the database based on DbDriverInterface
- DbDriverIntarce is the implementation to the Database connection. 
- Repository put all this together

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
$mapper->addFieldMap('createdate', 'created');
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
$orderMapper->addFieldAlias('id', 'orderid');
$itemMapper  = new \ByJG\MicroOrm\Mapper(...);
$itemMapper->addFieldAlias('id', 'itemid');

$query = \ByJG\MicroOrm\Query::getInstance()
    ->fields([
        'order.id as orderid',
        'item.id as itemid',
        /* Other fields here */
    ])
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

$mapper->addFieldMap(
    $property,
    $fielname,
    // Update Closure 
    // Returns the field value with a pre-processed function before UPDATE
    // If sets to NULL this field will never be updated/inserted
    function ($field, $instance) {
        return $field; 
    },
    // Select Closure 
    // Returns the field value with a post-processed value AFTER query from DB
    function ($field, $instance) {
        return $field; 
    }
);
```

## Pre-defined closures for field map:

*Mapper::defaultClosure($value, $instance)*

Defines the basic behavior for select and update fields;

*Mapper::doNotUpdateClosure($value, $instance)*

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

## Reuse Connection

The Repository receives a DbDriverInterface instance (connection). 
It is normal we create everytime a new connection. 
But if we need to reuse a previous connection we can use the
ConnectionManager object to handle it easier. 

```php
<?php
$connectionManager = new ConnectionManager();

$repo1 = new Repository($connectionManager->addConnection("uri://host"));

...

// If you the same Uri string the ConnectionManager will reuse 
// the last DbDriver instance created
$repo2 = new Repository($connectionManager->addConnection("uri://host"));

```

## Transaction

If all of DbDriver instance as created by the ConnectionManager you can create
database transactions including across different databases:

```php
<?php
$connectionManager = new ConnectionManager();

$connectionManager->beginTransaction();
$repo1 = new Repository($connectionManager->addConnection("uri1://host1"));
$repo2 = new Repository($connectionManager->addConnection("uri2://host2"));

// Do some Repository operations;
// ...

// commit (or rollback all transactions)
$connection->commitTransaction();
```


## Install

Just type: 

```
composer require "byjg/micro-orm=4.0.*"
```

# Running Tests

```php
vendor/bin/phpunit 
```

## Related Projects

- [Database Migration](https://github.com/byjg/migration)
- [Anydataset](https://github.com/byjg/anydataset)
- [PHP Rest Template](https://github.com/byjg/php-rest-template)
- [USDocker](https://github.com/usdocker/usdocker)
- [Serializer](https://github.com/byjg/serializer)

----
[Open source ByJG](http://opensource.byjg.com)
