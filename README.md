# MicroOrm for PHP
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/byjg/micro-orm/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/byjg/micro-orm/?branch=master)
[![Build Status](https://travis-ci.org/byjg/micro-orm.svg?branch=master)](https://travis-ci.org/byjg/micro-orm)
[![Code Coverage](https://scrutinizer-ci.com/g/byjg/micro-orm/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/byjg/micro-orm/?branch=master)


## Description

A micro framework for create a very simple decoupled ORM.
This library intended to be very small and very simple to use;

**Key Features**

* Can be used with any DTO, Entity, Model or whatever class with public properties or with getter and setter
* The repository support a variety of datasources: MySql, Sqlite, Postgres, MySQL, Oracle (see byjg/anydataset)
* A class Mapper is used for mapping the Entity and the repository
* Small and simple to use

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
$dataset = new ByJG\AnyDataset\Factory('mysql://user:password@server/schema');

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

#### Advanced uses

Get a collection using the query object:

```php
<?php
$query = new \ByJG\MicroOrm\Query();
$query->table('users')
    ->fields(['id', 'name'])
    ->where('name like :part', ['part' => 'A%']);

// Will return a collection o 'Users'
$collection = $repository->getByQuery($query);
```

Returning multiples entities with a query:

```php
<?php
$query = new \ByJG\MicroOrm\Query();
$query->table('order')
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

#### Tables with no AutoIncrements fields

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



## Install

Just type: `composer require "byjg/micro-orm=1.1.*"`

## Running Tests

```php
phpunit 
```


----
[Open source ByJG](http://opensource.byjg.com)
