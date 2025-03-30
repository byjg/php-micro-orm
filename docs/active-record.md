---
sidebar_position: 5
---

# Active Record

Active Record is the M in MVC - the model - which is the layer of the system responsible
for representing business data and logic. It facilitates the creation and use of
business objects whose data requires persistent storage to a database.
It is an implementation of the Active Record pattern, which provides an Object Relational Mapping system.

## How to Use Active Record

1. Create a model and add the annotations to the class and properties (
   see [Getting Started with Models and Attributes](getting-started-model.md))
2. Add the `ActiveRecord` trait to your model
3. Initialize the Active Record with the `initialize` static method (need to do only once)

### Example

```php
#[TableAttribute(tableName: 'my_table')]
class MyClass
{
    // Add the ActiveRecord trait to enable the Active Record
    use ActiveRecord;
    
    #[FieldAttribute(primaryKey: true)]
    public ?int $id;

    #[FieldAttribute(fieldName: "some_property")]
    public ?int $someProperty;
}
```

If you have more than one database connection, you can define a default database connection:

```php
// Set a default DBDriver
ORM::defaultDriver($dbDriver);
```

or call the `initialize` method with the database connection:

```php
// Initialize the Active Record with a specific DBDriver
MyClass::initialize($dbDriver);
```

## Using the Active Record

Once properly configured, you can use the Active Record pattern for database operations:

### Insert a New Record

```php
// Create a new instance
$myClass = MyClass::new();
$myClass->someProperty = 123;
$myClass->save();

// Or create with initial values
$myClass = MyClass::new(['someProperty' => 123]);
$myClass->save();
```

### Retrieve a Record

```php
$myClass = MyClass::get(1);
$myClass->someProperty = 456;
$myClass->save();
```

### Complex Filtering

```php
$myClassList = MyClass::filter((new IteratorFilter())->and('someProperty', Relation::EQUAL, 123));
foreach ($myClassList as $myClass) {
    echo $myClass->someProperty;
}
```

### Get All Records

```php
// Get all records (paginated, default is page 0, limit 50)
$myClassList = MyClass::all();

// Get page 2 with 20 records per page
$myClassList = MyClass::all(2, 20);
```

### Delete a Record

```php
$myClass = MyClass::get(1);
$myClass->delete();
```

### Refresh a Record

```php
// Retrieve a record
$myClass = MyClass::get(1);

// do some changes in the database
// OR
// expect that the record was changed by another process

// Get the updated data from the database
$myClass->refresh();
```

### Update a Model from Another Model or Array

```php
// Get a record
$myClass = MyClass::get(1);

// Update from array
$myClass->fill(['someProperty' => 789]);

// Update from another model
$anotherModel = MyClass::new(['someProperty' => 789]);
$myClass->fill($anotherModel);

// Save changes
$myClass->save();
```

### Convert to Array

```php
$myClass = MyClass::get(1);

// Convert to array (excluding null values)
$array = $myClass->toArray();

// Convert to array (including null values)
$array = $myClass->toArray(true);
```

### Using the `Query` Class

```php
$query = MyClass::joinWith('other_table');
// do some query here
// ...
// Execute the query
$myClassList = MyClass::query($query);
```

### Get Table Name

```php
$tableName = MyClass::tableName();
```

## Custom Mapper Configuration

By default, the Active Record uses the class attributes to discover the mapper configuration.
You can override this behavior by implementing the `discoverClass` method:

```php
class MyClass
{
    use ActiveRecord;
    
    // Override the default mapper discovery
    protected static function discoverClass(): string|Mapper
    {
        // Return a custom mapper
        return new Mapper(
            self::class,
            'custom_table',
            ['id']
        );
    }
}
```

## Advantages of Active Record

The Active Record pattern offers several advantages:

1. **Simplicity**: It provides a simple, intuitive interface for database operations
2. **Encapsulation**: Database operations are encapsulated within the model class
3. **Reduced Boilerplate**: Eliminates the need for separate repository classes for basic operations
4. **Fluent Interface**: Enables method chaining for a more readable code style

## When to Use Active Record vs. Repository

Both patterns have their place in application development:

- **Use Active Record when**:
  - You prefer a simpler, more direct approach
  - Your application has straightforward database operations
  - You want to reduce the number of classes in your codebase

- **Use Repository when**:
  - You need more control over database operations
  - Your application requires complex queries
  - You prefer a more explicit separation between models and database operations
  - You're implementing a domain-driven design approach 