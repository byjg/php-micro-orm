# Getting Started

## Defining the Model

The Model is a class will represent the data that you want to save or retrieve from the database.

The Model can be a simple class with public properties or a class with getter and setter.

You can add properties to the Model class to represent the fields in the database.

Here an example:

```php
#[TableAttribute(tableName: 'mytable')]
class MyModel
{
    #[FieldAttribute(primaryKey: true)]
    public ?int $id;

    #[FieldAttribute()]
    public ?string $name;

    #[FieldAttribute(fieldName: 'company_id')
    public ?int $companyId;
}
```

In this example, we have a class `MyModel` with three properties: `id`, `name`, and `companyId`.

The `id` property is marked as a primary key. The `name` property is a simple field. 
The `companyId` property is a field with a different name in the database `company_id`.

The `TableAttribute` is used to define the table name in the database.

## Table Structure

The table structure in the database should be like this:

```sql
CREATE TABLE `mytable` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `company_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

## Connecting the repository

After defining the Model, you can connect the Model with the repository.

```php
$dbDriver = \ByJG\AnyDataset\Db\Factory::getDbRelationalInstance('mysql://user:password@server/schema');

$repository = new \ByJG\MicroOrm\Repository($dbDriver, MyModel::class);
```

If necessary retrieve the mapper for advanced uses you can use the `getMapper` method.

```php
$mapper = $repository->getMapper();
```

## Querying the database

You can query the database using the repository.

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