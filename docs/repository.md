---
sidebar_position: 6
---

# The Repository class

The Repository class is the class that connects the model with the database. 

To achieve this, you need to create an instance of the Repository class and pass the database driver and the model class.

```php
$dbDriver = \ByJG\AnyDataset\Db\Factory::getDbRelationalInstance('mysql://user:password@server/schema');

$repository = new \ByJG\MicroOrm\Repository($dbDriver, MyModel::class);
```

## Read and Write Separation

You can use separate database connections for read and write operations, which is useful for database replication
scenarios:

```php
// Main connection used for reading
$dbDriverRead = \ByJG\AnyDataset\Db\Factory::getDbRelationalInstance('mysql://user:password@readserver/schema');

// Separate connection for write operations
$dbDriverWrite = \ByJG\AnyDataset\Db\Factory::getDbRelationalInstance('mysql://user:password@writeserver/schema');

$repository = new \ByJG\MicroOrm\Repository($dbDriverRead, MyModel::class);
$repository->addDbDriverForWrite($dbDriverWrite);
```

You can also create a read-only repository:

```php
$repository->setRepositoryReadOnly();
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
$model->setCompanyId(1);
$query = $repository->queryInstance($model);
```

You can also add additional tables for joins:

```php
$query = $repository->queryInstance($model, 'other_table', 'third_table');
```

### getMapper

The `getMapper` method returns the Mapper object that defines the relationship between the model and the database.

```php
$mapper = $repository->getMapper();
```

### getDbDriver

The `getDbDriver` method returns the database driver used for read operations. It allows you to use SQL commands
directly.
To find more about the database driver, please refer to the [AnyDataset documentation](https://github.com/byjg/anydataset)
and the [Query](querying-the-database.md).

```php
$dbDriver = $repository->getDbDriver();
$iterator = $dbDriver->getIterator('select * from mytable');
```

### getDbDriverWrite

The `getDbDriverWrite` method returns the database driver used for write operations. If no separate write driver was
configured, it returns the same driver as `getDbDriver()`.

```php
$dbDriverWrite = $repository->getDbDriverWrite();
```

## Repository Query Methods

The Repository class has the following methods for querying data:

### get

The `get` method retrieves a record from the database by the primary key.

```php
$myModel = $repository->get(1);
```

You can also use an array for composite primary keys:

```php
$myModel = $repository->get(['id' => 1, 'type' => 'user']);
```

### getByFilter

The `getByFilter` method retrieves an array of models from the database using an IteratorFilter.

```php
use ByJG\AnyDataset\Core\IteratorFilter;
use ByJG\AnyDataset\Core\Enum\Relation;

$filter = new IteratorFilter();
$filter->addRelation('name', Relation::EQUAL, 'John');

$result = $repository->getByFilter($filter);
```

You can also add pagination:

```php
// Get page 2 with 10 items per page
$result = $repository->getByFilter($filter, page: 2, limit: 10);
```

### getByQuery

The `getByQuery` method retrieves an array of the model from the database by a query.

```php
$query = Query::getInstance()
    ->where('company_id = :cid', ['cid' => 1])
    ->orderBy('name');

$result = $repository->getByQuery($query);
```

Or, build a query from a filter model:

```php
$filterModel = $repository->entity([
    'company_id' => 1
]);

$query = $repository->queryInstance($filterModel);
$query->orderBy('name');

$result = $repository->getByQuery($query);
```

You can also join with other tables/mappers:

```php
$query = Query::getInstance()
    ->table('users')
    ->join('orders', 'users.id = orders.user_id');

$result = $repository->getByQuery($query, [$orderRepository->getMapper()]);
```

### getByQueryRaw

The `getByQueryRaw` method retrieves raw data from the database (array of associative arrays) instead of model
instances:

```php
$query = Query::getInstance()
    ->field('name')
    ->field('COUNT(*) as count')
    ->groupBy('name');

$result = $repository->getByQueryRaw($query);
```

### getScalar

The `getScalar` method retrieves a single scalar value from the database by a query.

```php
$query = Query::getInstance()
    ->field('count(*)')
    ->where('company_id = :cid', ['cid' => 1]);
    
$result = $repository->getScalar($query);
```

### filterIn

The `filterIn` method retrieves records that match an array of values for a specific field:

```php
// Get users with ids in [1, 2, 5]
$users = $repository->filterIn([1, 2, 5]);

// Get users with specific names
$users = $repository->filterIn(['John', 'Jane', 'Bob'], 'name');
```

## Repository Update Methods

The Repository class has the following methods for modifying data:

### save

Update or insert a record in the database. If the primary key is set, it will update the record. Otherwise, it will insert a new record.

```php
$myModel = $repository->get(1);
$myModel->setName('New Name');
$repository->save($myModel);
```

You can also use update constraints:

```php
use ByJG\MicroOrm\UpdateConstraint;

$updateConstraint = new UpdateConstraint();
$updateConstraint->addField('name');
$updateConstraint->addField('email');

$myModel = $repository->get(1);
$myModel->setName('New Name');
$myModel->setEmail('newemail@example.com');
$myModel->setAge(30); // This won't be updated due to constraint

$repository->save($myModel, $updateConstraint);
```

### delete

Delete a record from the database by its primary key:

```php
$repository->delete(1);
```

Or by a composite key:

```php
$repository->delete(['id' => 1, 'type' => 'user']);
```

### deleteByQuery

Delete records from the database by a query allowing complex filters.

```php
$deleteQuery = DeleteQuery::getInstance()
    ->table('users')
    ->where('name like :name', ['name'=>'Jane%']);

$repository->deleteByQuery($deleteQuery);
```

## Hook Methods

### setBeforeInsert

Set a processor to be called before inserting a record. You can use either a closure or an implementation of
`EntityProcessorInterface`.

```php
// Using a closure
$repository->setBeforeInsert(function($instance) {
    // Modify the instance before inserting
    $instance->createdAt = date('Y-m-d H:i:s');
    return $instance;
});

// Using EntityProcessorInterface (recommended)
use ByJG\MicroOrm\Interface\EntityProcessorInterface;
use Override;

class BeforeInsertProcessor implements EntityProcessorInterface
{
    #[Override]
    public function process(mixed $instance): mixed
    {
        // Modify the instance before inserting
        if (is_array($instance) && !isset($instance['created_at'])) {
            $instance['created_at'] = date('Y-m-d H:i:s');
        }
        return $instance;
    }
}

$repository->setBeforeInsert(new BeforeInsertProcessor());
```

### setBeforeUpdate

Set a processor to be called before updating a record. You can use either a closure or an implementation of
`EntityProcessorInterface`.

```php
// Using a closure
$repository->setBeforeUpdate(function($instance) {
    // Modify the instance before updating
    $instance->updatedAt = date('Y-m-d H:i:s');
    return $instance;
});

// Using EntityProcessorInterface (recommended)
use ByJG\MicroOrm\Interface\EntityProcessorInterface;
use Override;

class BeforeUpdateProcessor implements EntityProcessorInterface
{
    #[Override]
    public function process(mixed $instance): mixed
    {
        // Modify the instance before updating
        if (is_array($instance) && !isset($instance['updated_at'])) {
            $instance['updated_at'] = date('Y-m-d H:i:s');
        }
        return $instance;
    }
}

$repository->setBeforeUpdate(new BeforeUpdateProcessor());
```

## Observers

### addObserver

Add an observer to the repository to monitor database operations.

```php
class MyObserver implements ObserverProcessorInterface
{
    public function update(ObserverData $observerData): void
    {
        // Process the update event
        $action = $observerData->getAction(); // INSERT, UPDATE, DELETE
        $model = $observerData->getModel();
        $oldModel = $observerData->getOldModel();
    }
}

$repository->addObserver(new MyObserver());
```

See [Observers](observers.md) for more details.