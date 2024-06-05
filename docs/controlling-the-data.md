# Controlling the data

You can control the data queried or updated by the micro-orm using the Mapper object.

Let's say you want to store the phone number only with numbers in the database, 
but in the entity class, you want to store with the mask.

You can add the `withUpdateFunction` and `withSelectFunction` to the FieldMap object
as you can see below:


```php
<?php
// Creating the mapping
$mapper = new \ByJG\MicroOrm\Mapper(...);

$fieldMap = FieldMap::create('propertyname') // The property name of the entity class
    // Returns the pre-processed value before UPDATE/INSERT the $field name
    // If the function returns NULL this field will not be included in the UPDATE/INSERT
    ->withUpdateFunction(function ($field, $instance) {
        return preg_replace('/[^0-9]/', '', $field);
    })
    // Returns the field value with a post-processed value of $field AFTER query from DB
    ->withSelectFunction(function ($field, $instance) {
        return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $field);
    })

$mapper->addFieldMapping($fieldMap);
```


## Pre-defined closures for field map

### Mapper::defaultClosure($value, $instance)

Defines the basic behavior for select and update fields; You don't need to set it. Just know it exists.

### Mapper::doNotUpdateClosure($value, $instance)

Defines a read-only field. It can be retrieved from the database but will not be updated.


## Before insert and update functions

You can also set closure to be applied before insert or update at the record level and not only in the field level.
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
