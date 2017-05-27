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

#### Using FieldAlias

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


#### Tables without auto increments fields

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

#### Applying functions for Select and Update

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



## Install

Just type: `composer require "byjg/micro-orm=2.0.*"`

## Running Tests

```php
phpunit 
```


----
[Open source ByJG](http://opensource.byjg.com)
