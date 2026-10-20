# Changelog - Version 7.0

> **Status: in development.** This document tracks changes landing on the `7.0` branch.
> Nothing here is released yet, and the contents may still change.

## Overview

Version 7.0 rewires the entire observer system on top of the low-level `DatabaseExecutor` observer
introduced by `byjg/anydataset-db` 7.0. The global `ORMSubject` singleton is removed: observer
dispatch now happens at the database-connection level, which makes observation complete (every
write built by MicroOrm notifies, not only `save()` and `deleteByQuery()`), faster (zero cost when
no observers are registered), and multi-connection safe.

`ObserverProcessorInterface` implementations require **no code change**.

## New Features

### Observers driven by the executor (ObserverBridge)
- All observer dispatch flows through `ObserverBridge`, a `DatabaseEventObserverInterface`
  implementation attached (one per `DatabaseExecutor`) on demand
- Writes now notify regardless of how they are executed: `save()`, `delete()`, `deleteByQuery()`,
  `bulkExecute()` (after commit; nothing on rollback), `buildAndExecute()`, and built queries
  executed directly on the executor
- `save()` keeps its exact previous semantics: Insert/Update observers still receive the entity
  rehydrated with generated keys (deferred dispatch)
- See: `docs/observers.md`

### OrmSqlStatement
- Every query builder `build()` now returns `OrmSqlStatement` (extends
  `ByJG\AnyDataset\Db\SqlStatement`), stamped with the ORM event (`Insert`, `Update`, `Delete`,
  `SoftDelete`) and the affected table for write builders
- `ObserverData::getStatement()` exposes it to observers (SQL text + params)
- `Updatable::build()` is declared to return `OrmSqlStatement`, so a custom `Updatable` subclass must return one.
  New `Updatable::getTable()`

### SoftDelete observer event
- New `ObserverEvent::SoftDelete` case: `delete()` on a mapper with soft delete enabled now fires
  an event (previously it fired nothing). The row still exists, with `deleted_at` set

### Before-execute hook with veto
- New `StatementHookInterface`: a processor implementing it is called before the SQL of an observed
  table executes, with full access to SQL and params. Throwing aborts the write

### Raw low-level observers through the Repository
- `Repository::addObserver()` also accepts a raw
  `ByJG\AnyDataset\Db\Interfaces\DatabaseEventObserverInterface`, attached directly to the
  repository executors (receives `BeforeQuery`/`AfterQuery` and `BeforeExecute`/`AfterExecute` for every
  statement, including reads and raw SQL)
- New `Repository::removeObserver()` for both observer kinds

### Relationship joins on the query builder
- New `QueryBasic::joinRelated()`, `leftJoinRelated()` and `rightJoinRelated()`: add a JOIN whose
  `ON` condition is derived from a registered `parentTable` relationship, connecting the table to one
  already in the query. If they are not directly related, the intermediate tables on the shortest
  relationship path are joined automatically; tables already in the query are skipped. The query
  keeps its own base table, so this composes with a repository query
- `ActiveRecord::joinWith()` now builds on `joinRelated()` (starting from the model's own table)
- `joinRelated()`/`leftJoinRelated()`/`rightJoinRelated()`/`joinWith()` accept a **model class**
  as well as a table name. Passing a class (e.g. `Project::class`) registers that entity's mapper
  on demand — reflection only, no DB connection — so its table and `parentTable` relationships
  become known even on a request that never touched its repository. To span a hidden intermediate,
  name each entity in the path, like Eloquent's `hasManyThrough` through-model:
  `Note::joinWith(Task::class, Project::class)`. New helper `ORM::getTableFromClass()` performs the
  resolution
- See: `docs/auto-discovering-relationship.md`

### UUID primary keys on every database
- `TableUuidPKAttribute` generates a version 7 UUID (time-ordered, RFC 9562) in PHP and writes it
  in the syntax of the repository's database: `X'...'` for a `BINARY(16)` on MySQL and SQLite,
  `0x...` for a `BINARY(16)` on SQL Server, and the quoted text for a native `uuid` on PostgreSQL
- `FieldUuidAttribute` writes the same per-database syntax, also for a `HexUuidLiteral` created
  without knowing the database (e.g. in a setter). It used to write `X'...'` everywhere, which
  PostgreSQL reads as a bit string and SQL Server rejects
- New `HexUuidLiteral::v7()` returns a new version 7 UUID, and `HexUuidLiteral::forDatabase($executor, $uuid)`
  returns the literal for the executor's database, for manual `where()` clauses
- `TableMySqlUuidPKAttribute`, `TableSqliteUuidPKAttribute` and `TablePgsqlUuidPKAttribute` are
  deprecated aliases of `TableUuidPKAttribute`. The MySQL one asked MySQL for
  `uuid_to_bin(uuid())`, so it failed on any other database; the SQLite one asked SQLite for
  `randomblob(16)`, a round trip for what PHP generates itself
- `FieldUuidAttribute` accepts `parentTable`, like `FieldAttribute`, to declare a foreign key relationship
- See: `docs/uuid-support.md`

### Backed enum properties
- A property typed with a backed enum (`public ?Status $status`) is saved as the enum's value,
  loaded back as the enum, and filters by its value in `queryInstance($model)`. It used to fail
  on save (the enum object reached the query), on load (`TypeError` assigning `'PAID'`) and in
  the filter. Done by `byjg/serializer` 7.0; tested in `RepositoryBackedEnumTest`
- See: `docs/getting-started-model.md`

## Breaking Changes

### Fixed: a primary key set on the instance is kept on insert
- `Repository::save()` replaced a primary key set on a new instance: the seed function of the table
  attribute (e.g. `TableUuidPKAttribute`) generated a new one, and without a seed function the value
  read back after the insert (the last insert id) took its place, so an `id` of 100 came back as 1.
  The given key is now inserted as is; a key is only generated or read back when it is empty

### Fixed: an empty primary key is left out of the INSERT
- A new instance without a key was inserted with an explicit `NULL` for it. MySQL and SQLite
  generate the key anyway, but PostgreSQL rejects it for a `serial` column
  (`null value in column "id" … violates not-null constraint`). An empty primary key is now left
  out of the INSERT, so the database fills it in, or the seed function's key is used

### Fixed: created_at, updated_at and a soft delete write a real date
- `NowUtcMapper` (the `CreatedAt` and `UpdatedAt` traits) and a soft delete (`deleted_at`) wrote
  "now" as the dialect's `sqlDate('Y-m-d H:i:s')`, an expression that formats a date as text. On
  PostgreSQL the text is rejected for a `timestamp` column (`expression is of type text`), and on
  SQL Server the `FORMAT()` pattern produced `YYYY-10-16 …`, which no date column accepts. Both now
  write `CURRENT_TIMESTAMP`, standard SQL that MySQL, PostgreSQL, SQLite, SQL Server and Oracle all
  store as a date. As before, it is the database's current time (its session time zone; UTC on SQLite)
- `NowUtcMapper` no longer needs a `DatabaseExecutor`; a test that mocked `sqlDate()` for it no
  longer applies

### UUID literals
- `PostgresUuidLiteral` writes `'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX'`, the text form a `uuid`
  column takes. It wrote `\x...` without quotes, which is not valid SQL
- The keys generated by the UUID table attributes are version 7 UUIDs. `TableMySqlUuidPKAttribute`
  generated version 1 (MySQL's `uuid()`), and the others 128 random bits with no version

### ORMSubject removed
- `ORMSubject` (global singleton) no longer exists. `Repository::addObserver()` keeps working with
  the same signature for `ObserverProcessorInterface`
- Remove any `ORMSubject::getInstance()->clearObservers()` calls (typically in test suites) —
  bridges die with their executors, there is no global state to clear

### Observer scope: global → per executor
- Observers now fire for writes flowing through the `DatabaseExecutor` instance(s) of the
  repository where they were registered. Two repositories over different executors no longer
  cross-notify, even on the same database. Register the observer on each connection if needed
- `addDbDriverForWrite()` re-attaches the repository observers to the new write executor

### More events than before
- Soft deletes (`SoftDelete`), `bulkExecute()` writes, `buildAndExecute()` and direct executions of
  built statements now notify. Review observers that assumed only `save()`/`deleteByQuery()` fired

### Event payload for non-save() writes
- Events from writes without an entity in scope (bulk, direct executions) carry the SQL parameters
  array in `getData()`/`getOldData()` instead of entity instances. Use `getStatement()` for the SQL

### Repository protected helpers
- `insert()`, `insertWithAutoInc()`, `insertWithKeyGen()` and `update()` now receive the pre-built
  `OrmSqlStatement` instead of the query builder. Subclasses overriding them must adopt the new
  signatures

### ObserverEvent enum
- New `SoftDelete` case: exhaustive `match` expressions over `ObserverEvent` in userland need a new
  arm

### ORM::getQueryInstance() removed
- `ORM::getQueryInstance(...$tables)` is removed. Build relationship joins with
  `Query::getInstance()->table($base)->joinRelated($related)` (or `Model::joinWith(...)` for Active
  Record) — it keeps your base table, supports INNER/LEFT/RIGHT, and auto-joins intermediates. The
  relationship registry (`addRelationship`/`getRelationship`/`getRelationshipData`) is unchanged

### Query::limit() second parameter renamed to $limit
- `Query::limit()` and `Union::limit()` named their second parameter `$end`, but it has always been the number
  of rows to return (the dialects build `LIMIT start, quantity`), not an end position. It is now `$limit`:
  `limit(int $start, int $limit)`. Positional calls are unaffected; calls using the named argument
  `limit(end: ...)` must change to `limit(limit: ...)`
- The protected property `Query::$limitEnd` that stores it is renamed to `Query::$limitQuantity`. Subclasses of
  `Query` reading or writing `$this->limitEnd` must use `$this->limitQuantity`

## Bug Fixes

- `Repository::getByFilter()` paging (and `ActiveRecord::all()`, which uses it) returned the wrong rows past the
  first page: it called `limit($page, ($page + 1) * $limit)`, which reads as start and quantity, so page 2 with
  limit 10 returned 30 rows from the 3rd. It now returns `$limit` rows starting at `$page * $limit`.
- `ActiveRecord::where()` declared `array|string $filter`, but an array was forwarded to
  `ActiveRecordQuery::createWhere(string $filter)` and always threw a `TypeError`. The parameter is now `string`,
  matching what it actually supports
- Relationships declared before the parent mapper was registered (`parentTable` pointing to a table not yet known)
  were never completed. `ORM::getRelationship()` now resolves them once the parent mapper is registered

## Migration from 6.x

| 6.x                                                      | 7.0                                                      |
|----------------------------------------------------------|----------------------------------------------------------|
| `ObserverProcessorInterface` implementations             | No change                                                |
| `$repository->addObserver($processor)`                   | No change                                                |
| `ORMSubject::getInstance()->clearObservers()`            | Remove the call                                          |
| Observer watching writes made through another connection | Register the observer on a repository of each connection |
| `Repository` subclass overriding `insert()`/`update()`   | Update to the new `OrmSqlStatement` signatures           |
| `match` over `ObserverEvent`                             | Add the `SoftDelete` arm                                 |
| `->limit(start: $s, end: $n)` (named argument)           | `->limit(start: $s, limit: $n)`                          |

## Requirements

- PHP 8.3, 8.4, 8.5 and 8.6 are now supported: `"php": ">=8.3 <8.7"`.
  The previous `<8.6` upper bound excluded PHP 8.6, since `<8.6` is exclusive.

### ByJG dependencies

- `byjg/anydataset-db` is now `^7.0`.
- `byjg/cache-engine` is now `^7.0`.

While 7.0 is unreleased these resolve to `7.0.x-dev` from each component's
`7.0` branch, via `minimum-stability: dev` with `prefer-stable: true`.

## Toolchain

- PHPUnit updated to `^12.5`.
- Psalm is installed as `psalm/phar` instead of `vimeo/psalm`.

  `vimeo/psalm` lists the PHP versions it supports and no published release includes
  8.6, so as a dev dependency it made `composer install` fail on the 8.6 build job
  before any test ran. `psalm/phar` requires only `php ^8.2` and bundles its own
  dependencies, so it installs on every PHP version in the matrix and cannot conflict
  with the project's. Psalm itself still refuses to *run* on 8.6, which is why the
  Psalm job uses 8.5. `composer psalm` runs it.

- PHPUnit 13 is deliberately **not** used. It requires PHP `>=8.4.1`, breaking the
  8.3 floor.

## Continuous Integration

- The tests also run on PostgreSQL and SQL Server (through `pdo_dblib`), which are added to
  `docker-compose.yml` and to the workflow. The `pdo_sqlsrv` test runs when the extension is
  installed.
- The build matrix now includes PHP 8.6.
- The Psalm job runs on PHP 8.5.

## Housekeeping

- `phpunit.xml.dist` renamed to `phpunit.xml`.
