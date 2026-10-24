---
sidebar_position: 7
---

# Active Record

## Pattern Overview

Active Record is an implementation of **Martin
Fowler's [Active Record pattern](https://martinfowler.com/eaaCatalog/activeRecord.html)**:

> *"An object that wraps a row in a database table or view, encapsulates the database access, and adds domain logic on
that data."*
>
> — Martin Fowler, Patterns of Enterprise Application Architecture

Active Record is the M in MVC - the model - which is the layer of the system responsible
for representing business data and logic. It facilitates the creation and use of
business objects whose data requires persistent storage to a database.

**Key Characteristics:**

- Each Active Record instance represents a single row in the database
- The object carries both data and behavior
- Provides methods like `save()`, `delete()`, `get()`, `all()`, etc.
- Database operations are directly available on the domain object

Behind the trait is the same `Repository` and `Mapper` used everywhere else in MicroORM:
the trait creates them for the model on the first call. To compare Active Record with
the repository approach, see [Choosing a Pattern](patterns.md).

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
// Set a default DatabaseExecutor
ORM::defaultDbDriver(DatabaseExecutor::using($dbDriver));
```

or call the `initialize` method with the database connection:

```php
// Initialize the Active Record with a specific DatabaseExecutor
MyClass::initialize(DatabaseExecutor::using($dbDriver));
```

`MyClass::reset($executor)` discards the current connection and, when an executor is given, initializes the model
with it.

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

### Query with Fluent API

The new fluent query API provides a more intuitive way to build and execute queries.
`MyClass::where()` takes a SQL condition string and its parameters; to filter with an `IteratorFilter`,
use `MyClass::filter()` or `MyClass::newQuery()->where($iteratorFilter)`.

```php
// Get first record matching criteria
$myClass = MyClass::where('someProperty = :value', ['value' => 123])
    ->first();

// Get first record or throw exception if not found
$myClass = MyClass::where('someProperty = :value', ['value' => 123])
    ->firstOrFail();

// Check if records exist
if (MyClass::where('someProperty > :min', ['min' => 100])->exists()) {
    echo "Records found!";
}

// Or throw an exception if none is found
MyClass::where('someProperty > :min', ['min' => 100])->existsOrFail();

// Get all matching records as array
$myClassList = MyClass::where('someProperty = :value', ['value' => 123])
    ->orderBy(['id DESC'])
    ->toArray();

// Chain multiple conditions
$myClass = MyClass::where('someProperty > :min', ['min' => 100])
    ->where('someProperty < :max', ['max' => 200])
    ->orderBy(['someProperty'])
    ->first();

// Build query step by step
$query = MyClass::newQuery()
    ->where('someProperty = :value', ['value' => 123])
    ->orderBy(['id DESC'])
    ->limit(0, 10);

$results = $query->toArray();
```

### Complex Filtering (Legacy)

```php
$myClassList = MyClass::filter((new IteratorFilter())->and('someProperty', Relation::Equal, 123));
foreach ($myClassList as $myClass) {
    echo $myClass->someProperty;
}
```

### Get All Records

```php
// Get all records (paginated, default is page 0, limit 50)
$myClassList = MyClass::all();

// Pages start at 0: page 2 with 20 records per page returns records 41 to 60
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

See [Choosing a Pattern](patterns.md). Both can be used in the same application.
