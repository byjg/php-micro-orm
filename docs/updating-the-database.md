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