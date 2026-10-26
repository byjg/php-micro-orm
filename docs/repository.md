---
sidebar_position: 8
---

# The Repository class

The Repository class is the class that connects the model with the database. 

To achieve this, you need to create an instance of the Repository class and pass the database driver and the model class.

```php
$dbDriver = \ByJG\AnyDataset\Db\Factory::getDbInstance('mysql://user:password@server/schema');
$executor = \ByJG\AnyDataset\Db\DatabaseExecutor::using($dbDriver);

$repository = new \ByJG\MicroOrm\Repository($executor, MyModel::class);
```

## Read and Write Separation

When you create a repository, it uses the same database connection for both read and write operations.

However, in some cases, you may want to use separate database connections for read and write operations. This is useful
for database replication scenarios where you have a master database for writing and one or more read-only replicas for
reading.

To configure separate database connections, you can use the `addDbDriverForWrite` method, which receives a
`DatabaseExecutor`:

```php
use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\AnyDataset\Db\Factory;

// Main connection used for reading
$executorRead = DatabaseExecutor::using(Factory::getDbInstance('mysql://user:password@readserver/schema'));

// Separate connection for write operations
$executorWrite = DatabaseExecutor::using(Factory::getDbInstance('mysql://user:password@writeserver/schema'));

$repository = new \ByJG\MicroOrm\Repository($executorRead, MyModel::class);
$repository->addDbDriverForWrite($executorWrite);
```

Alternatively, you can use the `setRepositoryReadOnly` method to make the repository read-only:

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

To join related tables, chain `joinRelated()` on the query
(see [Auto Discovering Relationship](auto-discovering-relationship.md)):

```php
$query = $repository->queryInstance($model)->joinRelated('other_table');
```

### getMapper

The `getMapper` method returns the Mapper object that defines the relationship between the model and the database.

```php
$mapper = $repository->getMapper();
```

### getExecutor

The `getExecutor` method returns the `DatabaseExecutor` used for read operations. It allows you to use SQL commands
directly.
To find more about the executor, please refer to the [AnyDataset documentation](https://github.com/byjg/anydataset)
and the [Query](querying-the-database.md).

```php
$executor = $repository->getExecutor();
$iterator = $executor->getIterator('select * from mytable');
```

### getExecutorWrite

The `getExecutorWrite` method returns the `DatabaseExecutor` used for write operations. If no separate write executor
was configured, it returns the same executor as `getExecutor()`.

```php
$executorWrite = $repository->getExecutorWrite();
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
$filter->and('name', Relation::Equal, 'John');

$result = $repository->getByFilter($filter);
```

You can also add pagination:

```php
// Pages start at 0: page 2 with 10 items per page returns items 21 to 30
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

You can also pass one or more update constraints, checked before an update
(see [Update Constraints](update-constraints.md)):

```php
use ByJG\MicroOrm\Constraint\RequireChangedValuesConstraint;

$myModel = $repository->get(1);
$myModel->setName('New Name');

// Throws RequireChangedValuesConstraintException if 'name' did not change
$repository->save($myModel, new RequireChangedValuesConstraint('name'));
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

Set a processor to be called before inserting a record. It accepts an implementation of `EntityProcessorInterface`
or the name of a class implementing it.

```php
use ByJG\MicroOrm\Interface\EntityProcessorInterface;
use Override;

class BeforeInsertProcessor implements EntityProcessorInterface
{
    #[Override]
    public function process(array $instance): array
    {
        // Modify the instance before inserting
        if (!isset($instance['created_at'])) {
            $instance['created_at'] = date('Y-m-d H:i:s');
        }
        return $instance;
    }
}

$repository->setBeforeInsert(new BeforeInsertProcessor());
```

### setBeforeUpdate

Set a processor to be called before updating a record. It accepts an implementation of `EntityProcessorInterface`
or the name of a class implementing it.

```php
use ByJG\MicroOrm\Interface\EntityProcessorInterface;
use Override;

class BeforeUpdateProcessor implements EntityProcessorInterface
{
    #[Override]
    public function process(array $instance): array
    {
        // Modify the instance before updating
        if (!isset($instance['updated_at'])) {
            $instance['updated_at'] = date('Y-m-d H:i:s');
        }
        return $instance;
    }
}

$repository->setBeforeUpdate(new BeforeUpdateProcessor());
```

### setValidator

Set a validator to be called on every `save()`, before the processors. It receives the instance, before any field
is mapped or converted, and whether the save is an insert; it rejects the instance by throwing an exception. It
accepts an implementation of `EntityValidatorInterface` or the name of a class implementing it. See
[Validating the entity before it is saved](controlling-the-data.md#validating-the-entity-before-it-is-saved).

```php
$repository->setValidator(new ProductValidator());
```

## Observers

### addObserver

Add an observer to the repository to monitor database operations.

```php
class MyObserver implements ObserverProcessorInterface
{
    public function process(ObserverData $observerData): void
    {
        $event = $observerData->getEvent();      // ObserverEvent::Insert, Update, Delete or SoftDelete
        $data = $observerData->getData();
        $oldData = $observerData->getOldData();
    }

    public function onError(Throwable $exception, ObserverData $observerData): void
    {
    }

    public function getObservedTable(): string
    {
        return 'mytable';
    }
}

$repository->addObserver(new MyObserver());
```

`removeObserver()` detaches it again.

See [Observers](observers.md) for more details.