---
sidebar_position: 18
---

# Building SQL Queries

The `build()` method is a core functionality of the Query class that constructs the final SQL statement from the query
builder.
This method is used internally by the ORM to generate the SQL query that will be executed against the database.

## Usage

```php
$query = Query::getInstance()
    ->table('users')
    ->fields(['id', 'name'])
    ->where('name like :name', ['name' => 'John%']);

$sqlStatement = $query->build($dbDriver);
```

## Parameters

- `?DbDriverInterface $dbDriver = null`: Optional database driver instance. If provided, it will be used to properly
  format table and field names according to the database syntax.

## Return Value

Returns a `SqlStatement` object containing:

- The generated SQL query string
- The parameters to be used in the prepared statement

## Features

The `build()` method handles:

1. **Field Selection**
    - Regular fields
    - Field aliases
    - Subqueries as fields
    - Mapper fields

2. **Table Operations**
    - Single table queries
    - Table aliases
    - Subqueries as tables
    - Joins (INNER, LEFT, RIGHT, CROSS)

3. **Query Conditions**
    - WHERE clauses
    - GROUP BY clauses
    - HAVING clauses
    - ORDER BY clauses
    - LIMIT clauses
    - TOP clauses (for SQL Server)

4. **Special Features**
    - Recursive queries (WITH RECURSIVE)
    - Soft delete handling
    - FOR UPDATE clauses
    - Literal values

## Examples

### Basic Query

```php
$query = Query::getInstance()
    ->table('users')
    ->fields(['id', 'name']);

$sqlStatement = $query->build();
// Result: SELECT id, name FROM users
```

### Query with Joins

```php
$query = Query::getInstance()
    ->table('users')
    ->join('orders', 'users.id = orders.user_id')
    ->fields(['users.id', 'users.name', 'orders.total']);

$sqlStatement = $query->build();
// Result: SELECT users.id, users.name, orders.total FROM users INNER JOIN orders ON users.id = orders.user_id
```

### Query with Subquery

```php
$subQuery = Query::getInstance()
    ->table('orders')
    ->fields(['user_id', 'COUNT(*) as order_count'])
    ->groupBy(['user_id']);

$query = Query::getInstance()
    ->table($subQuery, 'order_stats')
    ->fields(['order_stats.user_id', 'order_stats.order_count']);

$sqlStatement = $query->build();
// Result: SELECT order_stats.user_id, order_stats.order_count FROM (SELECT user_id, COUNT(*) as order_count FROM orders GROUP BY user_id) as order_stats
```

### Query with Complex Conditions

```php
$query = Query::getInstance()
    ->table('users')
    ->fields(['id', 'name', 'email'])
    ->where('age > :min_age', ['min_age' => 18])
    ->where('status = :status', ['status' => 'active'])
    ->orderBy(['name'])
    ->limit(0, 10);

$sqlStatement = $query->build();
// Result: SELECT id, name, email FROM users WHERE age > :min_age AND status = :status ORDER BY name LIMIT 0, 10
```

## Using Iterators

The Query Builder provides a convenient way to iterate over query results using the `buildAndGetIterator()` method. This
is particularly useful when dealing with large datasets as it processes records one at a time.

### Basic Iterator Usage

```php
$query = Query::getInstance()
    ->table('users')
    ->fields(['id', 'name', 'email']);

$iterator = $query->buildAndGetIterator($dbDriver);

foreach ($iterator as $row) {
    // Process each row
    echo "User: {$row['name']} ({$row['email']})\n";
}
```

### Iterator with Caching

You can also use iterators with caching to improve performance for frequently accessed data:

```php
$cache = new CacheQueryResult($cacheDriver, 'users_list', 3600); // Cache for 1 hour
$iterator = $query->buildAndGetIterator($dbDriver, $cache);

foreach ($iterator as $row) {
    // Process cached results
    echo "User: {$row['name']}\n";
}
```

### Reusing SQL Statements

In some cases, you might want to reuse the same SQL statement with different parameters. This can be done by building
the statement once and then using it multiple times with the iterator:

```php
// Build the query once
$query = Query::getInstance()
    ->table('users')
    ->fields(['id', 'name', 'email'])
    ->where('age > :min_age', ['min_age' => 18]);

$sqlStatement = $query->build();

// Use the same statement with different parameters
$params1 = ['min_age' => 18];
$iterator1 = $dbDriver->getIterator($sqlStatement->withParams($params1));
foreach ($iterator1 as $row) {
    echo "Adult user: {$row['name']}\n";
}

// Reuse with different parameters
$params2 = ['min_age' => 21];
$iterator2 = $dbDriver->getIterator($sqlStatement->withParams($params2));
foreach ($iterator2 as $row) {
    echo "Legal age user: {$row['name']}\n";
}
```

> **Note:** While this approach can be useful for performance optimization when you need to execute the same query with
> different parameters, it's generally recommended to use the Query Builder's methods directly for better maintainability
> and type safety.

## Updating Records

The Query Builder provides several ways to update records in the database:

### Single Record Update

```php
$updateQuery = UpdateQuery::getInstance()
    ->table('users')
    ->set('name', 'John Doe')
    ->set('email', 'john@example.com')
    ->where('id = :id', ['id' => 1]);

$sqlStatement = $updateQuery->build();
// Result: UPDATE users SET name = :name, email = :email WHERE id = :id
```

### Batch Update with Conditions

```php
$updateQuery = UpdateQuery::getInstance()
    ->table('users')
    ->set('status', 'inactive')
    ->set('updated_at', new Literal('NOW()'))
    ->where('last_login < :date', ['date' => '2024-01-01']);

$sqlStatement = $updateQuery->build();
// Result: UPDATE users SET status = :status, updated_at = NOW() WHERE last_login < :date
```

### Update with Joins

```php
$updateQuery = UpdateQuery::getInstance()
    ->table('users')
    ->join('orders', 'users.id = orders.user_id')
    ->set('users.status', 'premium')
    ->where('orders.total > :amount', ['amount' => 1000]);

$sqlStatement = $updateQuery->build();
// Result: UPDATE users INNER JOIN orders ON users.id = orders.user_id SET users.status = :status WHERE orders.total > :amount
```

## Notes

1. The `build()` method is typically used internally by the ORM. In most cases, you should use the repository's methods
   like `getByQuery()` instead of calling `build()` directly.
2. When using subqueries, you must provide an alias for the subquery table.
3. The method handles parameter binding automatically, making it safe against SQL injection.
4. The generated SQL is optimized for the specific database driver being used (if provided).
5. The method supports all standard SQL operations while maintaining database-agnostic syntax.
6. When using iterators, the results are processed one at a time, making it memory-efficient for large datasets.
7. Update queries require a WHERE clause to prevent accidental updates of all records.
