# Updating the Database

Once you have defined the model, (see [Getting Started](getting-started-model.md)) you can start to
interact with the database and doing queries, updates, and deletes.

## Update

Update a single record is simple as:

```php
<?php
$users = $repository->get(10);
$users->name = "New name";
$repository->save($users);
```

This code will update the record with the ID 10 and set the name to "New name".

## Insert

If you don't set the ID, the library will assume that you are inserting a new record.

```php
<?php
$users = new Users();
$users->name = "New name";
$repository->save($users);
```

## Using the UpdateQuery for Multiple Records

UpdateQuery allows you to update multiple records simultaneously with a single query. This is more efficient than
retrieving and updating individual records one by one when you need to apply the same changes to many records.

```php
<?php
$updateQuery = new \ByJG\MicroOrm\UpdateQuery();
$updateQuery->table('test');
$updateQuery->set('fld1', 'A');
$updateQuery->set('fld2', 'B');
$updateQuery->set('fld3', 'C');
$updateQuery->where('fld1 > :id', ['id' => 10]);
```

This code will update the table `test` and set the fields `fld1`, `fld2`, and `fld3` to `A`, `B`, and `C`
respectively for all records where `fld1` is greater than 10.

### Using Literal Values in Updates

Sometimes you need to update a field with a value that is calculated from the other fields from the table or with a
database function. For these cases, you can use the `setLiteral()` method:

```php
<?php
$updateQuery = new \ByJG\MicroOrm\UpdateQuery();
$updateQuery->table('products');
$updateQuery->setLiteral('counter', 'counter + 1');  // Increment the counter field by referencing its current value
$updateQuery->setLiteral('last_updated', 'NOW()');   // Use a database function to set current timestamp
$updateQuery->where('id = :id', ['id' => 10]);
```

The `setLiteral()` method allows you to use raw SQL expressions in your updates without having to manually create a
Literal object. This is particularly useful when you need to perform calculations based on existing field values or
apply database functions directly in your SQL query.

## Insert records with InsertQuery

You can insert records using the `InsertQuery` object. See an example:

```php
<?php
$insertQuery = new \ByJG\MicroOrm\InsertQuery();
$insertQuery->table('test');
$insertQuery->set('fld1', 'A');
$insertQuery->set('fld2', 'B');
$insertQuery->set('fld3', 'C');
```

## Insert records from another select

You can insert records from another select using the `InsertSelectQuery` object. See an example:

```php
<?php

// Define the query to select the records
$query = new QueryBasic();
$query->table('table2');
$query->field('fldA');
$query->field('fldB');
$query->field('fldC');
$query->where('fldA = :valueA', ['valueA' => 1]);

// Define the insert select query
$insertSelectQuery = new \ByJG\MicroOrm\InsertSelectQuery();
$insertSelectQuery->table('test');
$insertSelectQuery->fields(['fld1', 'fld2', 'fld3']);
$insertSelectQuery->fromQuery($query); // The query to select the records
```

## Insert records in batch

You can insert records in batch using the `InsertBulkQuery` object. See an example:

```php
<?php
$insertBulk = new InsertBulkQuery('test', ['fld1', 'fld2']);
$insertBulk->values(['fld1' => 'A', 'fld2' => 'B']);
$insertBulk->values(['fld1' => 'D', 'fld2' => 'E']);
$insertBulk->values(['fld1' => 'G', 'fld2' => 'H']);
```

By default, InsertBulkQuery uses a faster but less secure approach. To use parameterized queries for
better security (especially when handling user input), you can enable safe mode:

```php
<?php
$insertBulk = new InsertBulkQuery('test', ['fld1', 'fld2']);
$insertBulk->withSafeParameters(); // Enable safe parameterized queries
$insertBulk->values(['fld1' => $userInput1, 'fld2' => $userInput2]);
$insertBulk->values(['fld1' => $userInput3, 'fld2' => $userInput4]);
```

> **⚠️ Security Warning:** By default, the `InsertBulkQuery` implementation uses direct value embedding with
> basic escaping rather than parameterized queries. This makes it faster but potentially vulnerable to
> SQL injection attacks with untrusted data. Use the `withSafeParameters()` method when dealing with
> user input for better security, although this may reduce performance for large batch operations.
> For maximum security with user input, consider using the `InsertQuery` for individual inserts.

## Delete records

You can delete records using the `DeleteQuery` object. See an example:

```php
<?php
$deleteQuery = new \ByJG\MicroOrm\DeleteQuery();
$deleteQuery->table('test');
$deleteQuery->where('fld1 = :value', ['value' => 'A']);
```

## Execute multiple write queries in bulk

You can execute multiple write queries (insert, update, delete) sequentially within a single transaction using
`Repository::bulkExecute`.
This is useful when you need to perform a set of changes atomically: either all of them succeed, or none of them are
applied.

Signature:

```php
public function Repository::bulkExecute(array $queries, ?\ByJG\AnyDataset\Db\IsolationLevelEnum $isolationLevel = null): void
```

Rules and behavior:

- Accepts an array of QueryBuilderInterface or Updatable instances (e.g., InsertQuery, UpdateQuery, DeleteQuery). Each
  item is built and executed with the repository write driver.
- All queries are executed inside a transaction. If any query throws an exception, the transaction is rolled back and
  the exception is rethrown.
- Passing an empty array throws InvalidArgumentException.
- Passing an item that is not a QueryBuilderInterface or Updatable throws InvalidArgumentException.
- You can optionally pass a transaction isolation level using IsolationLevelEnum. The transaction allows joining an
  existing transaction if present.

Example:

```php
<?php
use ByJG\MicroOrm\Repository;
use ByJG\MicroOrm\InsertQuery;
use ByJG\MicroOrm\UpdateQuery;
use ByJG\MicroOrm\DeleteQuery;
use ByJG\AnyDataset\Db\Factory;

$db = Factory::getDbInstance('sqlite:///tmp/example.db');
$repository = new Repository($db, MyModel::class);

$insert = InsertQuery::getInstance('users', [
    'name' => 'Alice',
    'createdate' => '2020-01-01'
]);

$update = UpdateQuery::getInstance()
    ->table('users')
    ->set('name', 'Bob')
    ->where('id = :id', ['id' => 1]);

$delete = DeleteQuery::getInstance()
    ->table('users')
    ->where('name = :name', ['name' => 'OldName']);

$repository->bulkExecute([$insert, $update, $delete]);
```

Notes:

- Parameter names can overlap between queries (e.g., multiple queries using :name) because each query is built and
  executed independently.
- If you need a specific transaction isolation level, pass it as the second argument, e.g.,
  `IsolationLevelEnum::SERIALIZABLE`.