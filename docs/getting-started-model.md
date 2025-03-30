---
sidebar_position: 1
---

# Getting Started

## Defining the Model

The Model is a class will represent the data that retrieved and want to save into the database.

The Model can be:

* a simple class with public properties
* a class with getter and setter
* a mix of both

### Determining the Property Name:

Micro ORM determines the property name based on the following rules:

I'll convert that information into a table for you.

| Property Visibility  | Property Name Determination                                        |
|----------------------|--------------------------------------------------------------------|
| Public               | Property name is used directly                                     |
| Protected or Private | Property name is determined based on the getter/setter method name |

Example:

```php
public ?int $name = null;  // Property name is 'name' 

protected ?int $id = null; // Property name is 'id'

public function getId(): ?int   // Getter and setter are necessary.
{
    return $this->id;
}
public function setId(?int $id): void
{
    $this->id = $id;
}
```

### Requirements for the properties in the Model class:

| Property Characteristic    | Requirement                                              | Example                                                                                                                  |
|----------------------------|----------------------------------------------------------|--------------------------------------------------------------------------------------------------------------------------|
| Property with type         | Must be nullable with default value set (null preferred) | `public ?int $id = null;`                                                                                                |
| Property without type      | No need to set default value                             | `public $id;`                                                                                                            |
| Protected/Private property | Must have getter and setter public methods               | `protected ?int $id = null;`<br>`public function getId(): ?int {...};`<br>`public function setId(?int $id): void {...};` |

## Property Mapping Strategy with the Database

| Strategy            | Description                                                                                     | Example                                                                                                 |
|---------------------|-------------------------------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------|
| **Direct Matching** | The ORM matches entity property names to their corresponding database field names               | A property named `userid` would map to a database column named `userid`                                 |
| **Field Mapping**   | The ORM matches entity property names to their corresponding field mapping database field names | A property named `userId` would map to a database column named `user_id` if stated in the field mapping |

## Field Mapping Methods

| Method                                 | Description                               |
|----------------------------------------|-------------------------------------------|
| [Mapper](using-mapper-object.md) class | Define mappings using the Mapper class    |
| [Attributes](model-attribute.md)       | Define mappings using PHP 8.0+ attributes |

_See also: [Controlling the data](controlling-the-data.md) for advanced mapping_

### When to use each Mapper or Attribute?

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

* The `id` property is marked as a primary key.
* The `name` property is a direct match with the database field - No mapping is needed.
* The `companyId` property is a field with a different name in the database `company_id`.
* The `TableAttribute` is used to define the table name in the database.

## Connect the Model with the repository

After defining the Model, you can connect the Model with the repository.

```php
$dbDriver = Factory::getDbInstance('mysql://user:password@server/schema');
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