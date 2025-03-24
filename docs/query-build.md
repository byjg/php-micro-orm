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

## Notes

1. The `build()` method is typically used internally by the ORM. In most cases, you should use the repository's methods
   like `getByQuery()` instead of calling `build()` directly.
2. When using subqueries, you must provide an alias for the subquery table.
3. The method handles parameter binding automatically, making it safe against SQL injection.
4. The generated SQL is optimized for the specific database driver being used (if provided).
5. The method supports all standard SQL operations while maintaining database-agnostic syntax.
