---
sidebar_position: 9
---

# The Literal Object

When you are querying or updating the database, the parameters you pass are parsed to avoid SQL Injection.

The main reason to use literals is when you want to use database functions (like `NOW()` or `CURRENT_TIMESTAMP`) or
reference other fields from the table in your queries or updates. Literals allow these expressions to be passed directly
to the database without being treated as regular string values.

To pass a literal value you need to use the `Literal` object.

```php
<?php
use ByJG\MicroOrm\Literal;

$user = new Users();
$user->name = "John";
$user->createdate = new Literal('NOW()');
$repository->save($user);
```

In this example, the `createdate` field will be set to the current date and time based on the database server.

Note that `$user->createdate = 'NOW()'` will not work because the library will try to escape the value 
and will it will treat as string. Instead, you need to use the `Literal` object, as in the example above.

The same applies when you are doing a query:

```php
<?php
use ByJG\MicroOrm\Literal;

$query = new Query()::getInstance()
    ->field('name')
    ->where('createdate < :date', ['date' => new Literal('NOW()')]);
```

