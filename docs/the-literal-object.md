# The Literal Object

When you are querying or update the database, the parameters you pass are parsed to avoid SQL Injection.

Sometimes you need to pass a literal value to the database. For example, you need to pass a function like `NOW()` or `CURRENT_TIMESTAMP` to the database.

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

