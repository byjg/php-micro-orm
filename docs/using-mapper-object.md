---
sidebar_position: 4
---

# Using Mapper Object

The Mapper object is the main object that you will use to interact with the database and your model.

For the Majority of the cases you can use the model as you can see in the [Getting Started](getting-started-model.md) section.
This will create the mapper object automatically for you.

Create a mapper object directly in these scenarios:
- Use a PHP version earlier than 8.0 (soon this will be deprecated in the library)
- You have a Model object you cannot change (like a third-party library) or don't want to change.

## Creating the Mapper Object

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
$dataset = \ByJG\AnyDataset\Db\Factory::getDbInstance('mysql://user:password@server/schema');

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


