# Changelog - Version 7.0

## Overview

Version 7.0 rewires the entire observer system on top of the low-level `DatabaseExecutor` observer
introduced by `byjg/anydataset-db` 6.0. The global `ORMSubject` singleton is removed: observer
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

### SoftDelete observer event
- New `ObserverEvent::SoftDelete` case: `delete()` on a mapper with soft delete enabled now fires
  an event (previously it fired nothing). The row still exists, with `deleted_at` set

### Before-execute hook with veto
- New `StatementHookInterface`: a processor implementing it is called before the SQL of an observed
  table executes, with full access to SQL and params. Throwing aborts the write

### Raw low-level observers through the Repository
- `Repository::addObserver()` also accepts a raw
  `ByJG\AnyDataset\Db\Interfaces\DatabaseEventObserverInterface`, attached directly to the
  repository executors (receives `BEFORE/AFTER_QUERY` and `BEFORE/AFTER_EXECUTE` for every
  statement, including reads and raw SQL)
- New `Repository::removeObserver()` for both observer kinds

### Relationship joins on the query builder
- New `QueryBasic::joinRelated()`, `leftJoinRelated()` and `rightJoinRelated()`: add a JOIN whose
  `ON` condition is derived from a registered `parentTable` relationship, connecting the table to one
  already in the query. If they are not directly related, the intermediate tables on the shortest
  relationship path are joined automatically; tables already in the query are skipped. The query
  keeps its own base table, so this composes with a repository query
- `ActiveRecord::joinWith()` now builds on `joinRelated()` (starting from the model's own table)
- See: `docs/auto-discovering-relationship.md`

## Breaking Changes

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

## Migration from 6.x

| 6.x                                                      | 7.0                                                      |
|----------------------------------------------------------|----------------------------------------------------------|
| `ObserverProcessorInterface` implementations             | No change                                                |
| `$repository->addObserver($processor)`                   | No change                                                |
| `ORMSubject::getInstance()->clearObservers()`            | Remove the call                                          |
| Observer watching writes made through another connection | Register the observer on a repository of each connection |
| `Repository` subclass overriding `insert()`/`update()`   | Update to the new `OrmSqlStatement` signatures           |
| `match` over `ObserverEvent`                             | Add the `SoftDelete` arm                                 |
