# QueryRaw

QueryRaw lets you execute a raw SQL statement while still benefiting from the repository pipeline (parameter binding,
connection handling, iterators, and optional caching when used via SqlStatement).

Important: QueryRaw does NOT generate or adapt SQL based on the DbDriver/DB dialect. It passes through exactly the SQL
string you provide. That means the SQL you write must already be valid for the target database engine. Because of this,
QueryRaw should be used only for very specific use cases where the high-level Query/Update/Insert/Delete builders cannot
express what you need.

When to use QueryRaw

- Vendor-specific features that are not covered by the query builders (e.g., dialect functions, specialized hints).
- One-off statements where you accept tight coupling to a single database dialect.
- Chaining within bulk operations when you need to run a raw select right after a write.

When NOT to use QueryRaw

- Everyday selects/joins/filters/limits that can be expressed with Query or Union.
- Inserts/updates/deletes that can be handled by InsertQuery/UpdateQuery/DeleteQuery.
- Anywhere you want portability across databases or automatic dialect handling.

Behavior

- build(): returns a SqlObject with your SQL and parameters unchanged. The optional DbDriver parameter is ignored for
  SQL generation.
- buildAndGetIterator($dbDriver): executes your SQL against the provided driver and returns a GenericIterator.
- Parameter binding: pass parameters as an array (named or positional) and the underlying driver will bind them.

Examples

1) Basic raw select returning rows as arrays

```php
use ByJG\MicroOrm\QueryRaw;

$query = QueryRaw::getInstance(
    'select id, name from users where name like :part',
    ['part' => 'A%']
);

$rows = $repository->getByQueryRaw($query); // array<array<string, mixed>>
```

2) Using dialect-specific functions (DB-specific SQL)

```php
// Example for SQLite using julianday(); not portable to other databases
$query = QueryRaw::getInstance(
    "select name, julianday('2020-06-28') - julianday(createdate) as days from users limit 1"
);

$rows = $repository->getByQueryRaw($query);
// e.g., [ [ 'name' => 'Jane Doe', 'days' => 1271.0 ] ]
```

3) Bulk execution: insert followed by selecting last inserted id

```php
use ByJG\MicroOrm\InsertQuery;
use ByJG\MicroOrm\QueryRaw;

$insert = InsertQuery::getInstance('users', [
    'name' => 'Charlie',
    'createdate' => '2025-01-01',
]);

// Use DB helper to get the correct SQL for your driver
$selectLastId = QueryRaw::getInstance(
    $repository->getDbDriver()->getDbHelper()->getSqlLastInsertId()
);

$it = $repository->bulkExecute([$insert, $selectLastId]);
$result = $it->toArray();
// $result is the rows from the last statement (the select)
```

Notes

- QueryRaw ties your code to the specific SQL dialect you write. If you switch databases, you may need to rewrite the
  raw SQL.
- Always use bound parameters to avoid SQL injection; pass them in the second argument to getInstance().
- For portable queries, prefer Query/Union and the provided Insert/Update/Delete builders.
