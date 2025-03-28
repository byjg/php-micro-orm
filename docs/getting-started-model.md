---
sidebar_position: 1
---

# Getting Started

## Defining the Model

The Model is a class will represent the data that retrieved and want to save into the database.

The Model can be:

* a simple class with public properties
* a class with getter and setter

### Requirements for the properties in the Model class:

* If the property has a type, then must be nullable and the default value must be set (null preferred or any other
  value).

```php
// Typed property
public ?int $id = null;
```

* If there is no type, then does not need to have default value.

```php
// Untyped property
public $id;
```

* If the property is protected or private, you must have a getter and setter for this property.

```php
// Protected property
protected ?int $id = null;
public function getId(): ?int
{
    return $this->id;
}
public function setId(?int $id): void
{
    $this->id = $id;
}
```

* The properties name is not related to the getter and setter, but the name of the property.

```php
// In the example above, the property is 'id' and the getter is 'getId' and the setter is 'setId'
```

## Relate the Model with the database

The model doesn't need to have the same name as the database fields.

It can be done in two ways:

* Using the [Mapper](using-mapper-object.md) class and [Controlling the data](controlling-the-data.md)
* Using [Attributes](model-attribute.md).

### When to use each one?

The `Mapper` class is more flexible and can be used in any PHP version. Use cases:

* You have a legacy class and cannot change it
* You don't want change the Model class
* You can't change the Model class

The `Attributes` is more simple and can be used in PHP 8.0 or later. Use cases:

* You want a more simple way to define the Model
* You want to use the latest PHP features
* You can have a more simple way to define the Model

### Example

Let's say we have a model with the following properties and side by side with the database fields:

| Model Property | Database Field |
|----------------|----------------|
| id             | id             |
| name           | name           |
| companyId      | company_id     |

And we want to map the Model properties to the database fields using Attributes:

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

and the table:

```sql
CREATE TABLE `mytable`
(
    `id`         int(11) NOT NULL AUTO_INCREMENT,
    `name`       varchar(255) DEFAULT NULL,
    `company_id` int(11)      DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

In this example, we have a class `MyModel` with three properties: `id`, `name`, and `companyId`.

* The `id` property is marked as a primary key. The `name` property is a simple field.
* The `companyId` property is a field with a different name in the database `company_id`.
* The `TableAttribute` is used to define the table name in the database.

## Connect the Model with the repository

After defining the Model, you can connect the Model with the repository.

```php
$dbDriver = Factory::getDbRelationalInstance('mysql://user:password@server/schema');
$repository = new Repository($dbDriver, MyModel::class);
```

### Querying the database

You can query the database using the [repository](repository.md).

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