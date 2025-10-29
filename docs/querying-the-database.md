---
sidebar_position: 2
---

# Querying the database

Once you have the model and the repository, you can query the database using the repository.

The results will be returned as a collection of the model defined in the repository.

e.g.:

```php
$query = \ByJG\MicroOrm\Query::getInstance()
    ->table('users')
    ->fields(['id', 'name'])
    ->where('name like :part', ['part' => 'A%']);

// Will return a collection o 'Users'
$collection = $repository->getByQuery($query);
```

or, you can also get a single record by its ID:

```php
$myModel = $repository->get(1);
```

Some cases you need to query a database using join and want to return a collection of objects from different tables.

```php
<?php
$query = \ByJG\MicroOrm\Query::getInstance()
    ->table('order')
    ->join('item', 'order.id = item.orderid')
    ->where('name like :part', ['part' => 'A%']);

// Will return a collection of Orders and Items:
// $collection = [
//     [ $order, $item ],
//     [ $order, $item ],
//     ...
// ];
$collection = $orderRepository->getByQuery(
    $query,
    // Add additional mappers to the query
    [
        $itemRepository->getMapper()
    ]
);



## QueryBasic Object

The query basic object contains the essential methods to query the database. It can be used 
in the `Union` class, and can be converted to a `Updatable` object.

### Methods

#### table(string $tableName, string $alias = null)

The table to query.

#### fields(array $fields)

Array of fields to retrieve. if not set, will retrieve all fields.

e.g.:

```php
->fields(['id', 'name'])
```

#### field(string $field, string $alias = null)

A single field to retrieve. You can set an alias for the field.

e.g.:

```php
->field('username', 'login');
```

#### where(string $where, array $params)

The where clause. You can use placeholders in the where clause.

e.g.:

```php
->where('name like :part', ['part' => 'A%'])
```

The placeholders can be named or unnamed. 
If you use named placeholders, you need to pass an associative array with the values.

If you use unnamed placeholders, you need to pass an array with the values in the same order as the placeholders.

* Named placeholders are defined by a colon followed by the placeholder name;
* Unnamed placeholders are defined by a question mark. The arguments are positional

#### whereIsNull(string $field)

Add a WHERE field IS NULL condition.

e.g.:

```php
->whereIsNull('deleted_at')
```

#### whereIsNotNull(string $field)

Add a WHERE field IS NOT NULL condition.

e.g.:

```php
->whereIsNotNull('email')
```

#### whereIn(string $field, array $values)

Add a WHERE field IN (values) condition.

e.g.:

```php
->whereIn('status', ['active', 'pending'])
```

#### join(string $table, string $on, string $alias = null)

Join another table.

e.g.:

```php
->table('order', 'o')
->join('item', 'o.id = i.orderid', 'i')
```

#### leftJoin(string $table, string $on, string $alias = null)

Left Join another table.

#### rightJoin(string $table, string $on, string $alias = null)

Right Join another table.

#### groupBy(array $field)

Group by a field or an array of fields.

e.g.:

```php
->groupBy(['field1', 'field2'])
```

#### having(string $filter)

Having clause for filtering grouped results.

e.g.:

```php
->having('count(field1) > 10')
```

## Query Object

The Query object extends the QueryBasic object and adds more methods to query the database.

### Methods

#### orderBy(array $field)

Order by a field or an array of fields.

e.g.:

```php
->orderBy(['field1', 'field2'])
```

#### limit(int $start, int $pageSize)

Limit the number of records to retrieve.

e.g.:

```php
->limit(10, 20)
```

#### top(int $top)

Get the first N records.

e.g.:

```php
->top(10)
```

## Union Object

The Union object is used to combine two queries. Since the Union operation is a set operation,
the queries must have the same fields and the order, group by, and limit must be defined by the
Union object.

e.g.

```php
$query1 = \ByJG\MicroOrm\Query::getInstance()
    ->table('users')
    ->fields(['id', 'name'])
    ->where('name like :part', ['part' => 'A%']);

$query2 = \ByJG\MicroOrm\Query::getInstance()
    ->table('customers')
    ->fields(['id', 'name'])
    ->where('name like :part', ['part' => 'A%']);

$union = \ByJG\MicroOrm\Union::getInstance()
    ->addQuery($query1)
    ->addQuery($query2)
    ->orderBy(['name'])
    ->limit(10, 20);
```
