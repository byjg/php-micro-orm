# Updating the Database

Once you have defined the model, (see [Getting Started](getting-started-model.md)) you can start to interact with the database
and doing queries, updates, and deletes.

Update a single record is simple as:

```php
<?php
$users = $repository->get(10);
$users->name = "New name";
$repository->save($users);
```

This code will update the record with the ID 10 and set the name to "New name".

The idea is to insert a new record. If you don't set the ID, the library will assume that you are inserting a new record.

```php
<?php
$users = new Users();
$users->name = "New name";
$repository->save($users);
```

## Advanced Cases

In some cases you need to update multiples records at once. See an example:

```php
<?php
$updateQuery = new \ByJG\MicroOrm\UpdateQuery();
$updateQuery->table('test');
$updateQuery->set('fld1', 'A');
$updateQuery->set('fld2', 'B');
$updateQuery->set('fld3', 'C');
$updateQuery->where('fld1 > :id', ['id' => 10]);
```

This code will update the table `test` and set the fields `fld1`, `fld2`, and `fld3` to `A`, `B`, and `C` respectively where the `fld1` is greater than 10.