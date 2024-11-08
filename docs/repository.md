# The Repository class

The Repository class is the class that connects the model with the database. 

To achieve this, you need to create an instance of the Repository class and pass the database driver and the model class.

```php
$dbDriver = \ByJG\AnyDataset\Db\Factory::getDbRelationalInstance('mysql://user:password@server/schema');

$repository = new \ByJG\MicroOrm\Repository($dbDriver, MyModel::class);
```

## Repository Helper Methods

The Repository class has the following helper methods:

### entity

The `entity` method creates an instance of the model with the properties set.

```php
$model = $repository->entity([
    'company_id' => 1
]);
```

### queryInstance

The `queryInstance` method creates an instance of the Query class pre-setting the table and filters
based on the Mapper and in the values of the model.

```php
$model = $repository->entity();
$model->setCompanyId(1)
$query = $repository->queryInstance($model);
```

### getDbDriver

The `getDbDriver` method returns the database driver. It allows you to use SQL commands directly. 
To find more about the database driver, please refer to the [AnyDataset documentation](https://github.com/byjg/anydataset)
and the [Query](querying-the-database.md).

```php
$dbDriver = $repository->getDbDriver();
$iterator = $dbDriver->getIterator('select * from mytable');
```


## Repository Query Methods

The Repository class has the following methods:

### get

The `get` method retrieves a record from the database by the primary key.

```php
$myModel = $repository->get(1);
```

### getByQuery

The `getByQuery` method retrieves an array of the model from the database by a query.

```php
$query = Query::getInstance()
    ->where('company_id = :cid', ['cid' => 1])
    ->orderBy('name');

$result = $repository->getByQuery($query);
```

or, the same example above:

```php
$filterModel = $repository->entity([
    'company_id' => 1
]);

$query = $repository->queryInstance($filterModel);
$query->orderBy('name');
```

### getScalar

The `getScalar` method retrieves a scalar value from the database by a query.

```php
$query = Query::getInstance()
    ->field('count(*)')
    ->where('company_id = :cid', ['cid' => 1]);
    
$result = $repository->getScalar($query);
```

### save

Update or insert a record in the database. If the primary key is set, it will update the record. Otherwise, it will insert a new record.

```php
$myModel = $repository->get(1);
$myModel->setName('New Name');
$repository->save($myModel);
```

### delete

Delete a record from the database from the primary key.

```php
$myModel = $repository->delete(1);
```

### deleteByQuery

Delete a record from the database by a query allowing complex filters.

```php
$query = Updatable::getInstance()
    ->table($this->userMapper->getTable())
    ->where('name like :name', ['name'=>'Jane%']);

$this->repository->deleteByQuery($query);
```