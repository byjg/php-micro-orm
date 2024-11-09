# Active Record

Active Record is the M in MVC - the model - which is the layer of the system responsible
for representing business data and logic. Active Record facilitates the creation and use of
business objects whose data requires persistent storage to a database.
It is an implementation of the Active Record pattern which itself is a description of an
Object Relational Mapping system.

## How to use Active Record

- Create a model and add the annotations to the class and properties. (see [Model](model.md))
- Add the `ActiveRecord` trait to your model.
- Initialize the Active Record with the `initialize` static method (need to do only once)

e.g.:

```php
<?php
[TableAttribute('my_table')]
class MyClass
{
    // Add the ActiveRecord trait to enable the Active Record
    use ActiveRecord;
    
    #[FieldAttribute(primaryKey: true)]
    public ?int $id;

    #[FieldAttribute(fieldName: "some_property")]
    public ?int $someProperty;
}

// Initialize the Active Record
MyClass::initialize($dbDriver);
```

## Using the Active Record

Once is properly configured you can use the Active Record to save, update, delete and retrieve the data.

### Insert a new record

```php
<?php
// Create a new instance
$myClass = MyClass::new();
$myClass->someProperty = 123;
$myClass->save();

// Another example Create a new instance 
$myClass = MyClass::new(['someProperty' => 123]);
$myClass->save();
```

### Retrieve a record

```php
<?php
$myClass = MyClass::get(1);
$myClass->someProperty = 456;
$myClass->save();
```

### Complex Filter

```php
<?php
$myClassList = MyClass::filter((new IteratorFilter())->and('someProperty', Relation::EQUAL, 123));
foreach ($myClassList as $myClass) {
    echo $myClass->someProperty;
}
```

### Delete a record

```php
<?php
$myClass = MyClass::get(1);
$myClass->delete();
```

### Using the `Query` class

```php
<?php
$query = MyClass::joinWith('other_table');
// to some query here
$myClassList = MyClass::query($query);
```
