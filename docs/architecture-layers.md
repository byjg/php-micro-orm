# Architecture Layers: Infrastructure vs Domain

## Overview

The MicroORM is designed with a clear separation between **Infrastructure Layer** (raw data access) and **Domain Layer
** (entity-aware operations). Understanding when to use each layer is crucial for writing maintainable code.

## Architectural Foundation

This architecture follows **Martin Fowler's Enterprise Application Architecture patterns**, specifically:

### Repository Pattern

> *"Mediates between the domain and data mapping layers using a collection-like interface for accessing domain
objects."*
>
> — Martin
> Fowler, [Patterns of Enterprise Application Architecture](https://martinfowler.com/eaaCatalog/repository.html)

**In MicroORM:**

- The `Repository` class acts as a collection-like interface
- Isolates domain objects from database access code
- Provides a clean separation between domain and data layers
- Handles entity lifecycle (CRUD operations)

### Data Mapper Pattern

> *"A layer of software that separates the in-memory objects from the database. Its responsibility is to transfer data
between the two and also to isolate them from each other."*
>
> — Martin
> Fowler, [Patterns of Enterprise Application Architecture](https://martinfowler.com/eaaCatalog/dataMapper.html)

**In MicroORM:**

- The `Mapper` class defines the relationship between entities and database tables
- Translates between domain objects (entities) and database rows
- Keeps domain objects independent of database schema
- Allows field name mapping, transformations, and type conversions

**Why These Patterns Matter:**

- ✅ **Testability**: Mock repositories without touching the database
- ✅ **Flexibility**: Change database schema without changing domain objects
- ✅ **Separation of Concerns**: Domain logic stays pure, database logic stays isolated
- ✅ **Maintainability**: Clear boundaries make code easier to understand and modify

### Active Record Pattern (Alternative Approach)

> *"An object that wraps a row in a database table or view, encapsulates the database access, and adds domain logic on
that data."*
>
> — Martin
> Fowler, [Patterns of Enterprise Application Architecture](https://martinfowler.com/eaaCatalog/activeRecord.html)

**In MicroORM:**

- The `ActiveRecord` trait provides static methods like `get()`, `save()`, `delete()`, etc.
- Each Active Record instance represents a single database row
- Database operations are directly available on domain objects
- Simpler than Repository/Data Mapper for straightforward CRUD applications

**Pattern Comparison:**

```
Repository + Data Mapper           Active Record
├─ Domain objects are pure          ├─ Domain objects know about DB
├─ More layers/complexity           ├─ Fewer layers/simpler
├─ Better for complex domains       ├─ Better for simple domains
├─ Easier to test (DI)              ├─ Harder to test (static methods)
└─ More flexible                    └─ More convenient
```

**Choosing Your Pattern:**

- Use **Active Record** for: Simple apps, prototypes, CRUD-heavy applications
- Use **Repository + Data Mapper** for: Complex domain logic, Domain-Driven Design, enterprise applications

See [Active Record Documentation](active-record.md) for detailed usage examples.

## The Two Layers

### Infrastructure Layer

**Purpose**: Raw database access without entity knowledge
**Key Methods**: `Query::buildAndGetIterator()`, `Query::build()`
**Returns**: Raw database rows (associative arrays)

### Domain Layer

**Purpose**: Entity-aware data access with automatic mapping
**Key Methods**: `Repository::getIterator()`, `Repository::getByQuery()`
**Returns**: Domain entities (objects)

## When to Use Each Layer

### Use Infrastructure Layer (`Query::buildAndGetIterator()`) For:

✅ **Migration Scripts**

```php
// Migration: Export data for backup
$query = Query::getInstance()
    ->table('users')
    ->where('created_at < :cutoff', ['cutoff' => '2020-01-01']);

$rows = $query->buildAndGetIterator(DatabaseExecutor::using($driver))->toArray();
file_put_contents('backup.json', json_encode($rows));
```

✅ **Utility/Admin Tools**

```php
// Admin tool: Generate report with raw data
$query = QueryRaw::getInstance("
    SELECT DATE(created_at) as date, COUNT(*) as count
    FROM users
    GROUP BY DATE(created_at)
");

$stats = $query->buildAndGetIterator(DatabaseExecutor::using($driver))->toArray();
```

✅ **Testing Query Building Logic**

```php
// Test: Verify SQL generation
public function testQueryBuilder()
{
    $query = Query::getInstance()
        ->table('users')
        ->where('status = :status', ['status' => 'active']);

    $sql = $query->build($driver)->getSql();
    $this->assertEquals('SELECT * FROM users WHERE status = :status', $sql);
}
```

✅ **Working Without a Repository**

```php
// Standalone script without entity models
$query = Query::getInstance()
    ->table('logs')
    ->where('level = :level', ['level' => 'ERROR']);

$errors = $query->buildAndGetIterator(DatabaseExecutor::using($driver))->toArray();
```

### Use Domain Layer (`Repository` methods) For:

✅ **Application/Business Logic**

```php
// Application: Get active users
$query = $userRepo->queryInstance()
    ->where('status = :status', ['status' => 'active']);

$users = $userRepo->getIterator($query)->toEntities(); // Returns User[] objects
```

✅ **Standard CRUD Operations**

```php
// Business logic: Find user by email
$user = $repository->getByFilter(['email' => 'user@example.com']);

// Update user
$user->setName('New Name');
$repository->save($user);
```

✅ **Complex Queries with Entity Transformation**

```php
// Multi-table JOIN with automatic entity mapping
$query = Query::getInstance()
    ->table('users')
    ->join('info', 'users.id = info.user_id')
    ->where('users.status = :status', ['status' => 'active']);

// Returns array of [User, Info] entity pairs
$results = $userRepo->getByQuery($query, [$infoMapper]);
```

## Architecture Comparison

```
┌─────────────────────────────────────────────────────────────────────────┐
│                          INFRASTRUCTURE LAYER                           │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  Query::buildAndGetIterator(DatabaseExecutor $executor)                 │
│  ├─ Returns: GenericIterator (raw arrays)                               │
│  ├─ No entity knowledge                                                 │
│  ├─ Stateless execution                                                 │
│  └─ Use for: migrations, utilities, admin tools                         │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
                                    ▲
                                    │
                                    │ build()
                                    │
┌────────────────────────────────────────────────────────────────────────┐
│                             DOMAIN LAYER                               │
├────────────────────────────────────────────────────────────────────────┤
│                                                                        │
│  Repository::getIterator(QueryBuilderInterface $query)                 │
│  ├─ Returns: GenericIterator (with entity transformation)              │
│  ├─ Uses mapper for automatic transformation                           │
│  ├─ Integrated with repository lifecycle                               │
│  └─ Use for: application logic, business operations                    │
│                                                                        │
│  Repository::getByQuery(QueryBuilderInterface $query, array $mappers)  │
│  ├─ Returns: Entity[] or Array<int, Entity[]>                          │
│  ├─ Handles single-mapper (efficient) and multi-mapper (JOINs)         │
│  ├─ Intelligent entity boundary detection                              │
│  └─ Use for: complex queries, JOIN operations                          │
│                                                                        │
└────────────────────────────────────────────────────────────────────────┘
```

## Multi-Mapper Logic: Why It Belongs in Repository

### The Problem

When executing a JOIN query, the result set contains columns from multiple tables:

```sql
SELECT users.*, info.*
FROM users
JOIN info ON users.id = info.user_id
```

Result:

```
| id | name      | id | user_id | property |
|----|-----------|----|---------│----------|
| 1  | John Doe  | 1  | 1       | 30.4     |
```

This row contains data for **two entities**: User and Info. The Query layer doesn't know where one entity ends and
another begins.

### The Solution: Repository Intelligence

`Repository::getByQuery()` intelligently handles this by accepting multiple mappers for JOIN queries:

```php
// Setup: Create mappers for both entities
$userMapper = new Mapper(User::class, 'users', 'id');
$infoMapper = new Mapper(Info::class, 'info', 'id');

$userRepository = new Repository(DatabaseExecutor::using($driver), $userMapper);

// Build a JOIN query
$query = Query::getInstance()
    ->table('users')
    ->join('info', 'users.id = info.user_id')
    ->where('users.status = :status', ['status' => 'active']);

// Execute with multiple mappers - Repository handles the complexity!
$results = $userRepository->getByQuery($query, [$infoMapper]);

// Results structure:
// [
//     [$userEntity1, $infoEntity1],  // First row mapped to User and Info
//     [$userEntity2, $infoEntity2],  // Second row mapped to User and Info
//     ...
// ]

foreach ($results as [$user, $info]) {
    echo $user->getName() . " has property: " . $info->getProperty() . "\n";
}
```

**What happens internally:**

1. Repository detects multiple mappers (User + Info)
2. Executes the query and gets raw rows
3. For each row, creates **two separate entities**:
    - Uses `$userMapper` to extract User fields → creates User object
    - Uses `$infoMapper` to extract Info fields → creates Info object
4. Returns array of entity pairs

**Single Mapper (Optimized Path):**

```php
// When you only need one entity type, it's more efficient
$query = Query::getInstance()
    ->table('users')
    ->where('status = :status', ['status' => 'active']);

$users = $userRepository->getByQuery($query); // No additional mappers
// Returns: [User, User, User, ...] - Just User entities
```

### Why This Logic Belongs in Repository, Not Query:

1. **Entity mapping is domain logic**, not data access logic
2. **Multi-mapper queries need entity boundaries** - only the Repository knows which columns belong to which entity
3. **Query layer stays agnostic** - keeps infrastructure concerns separate from domain concerns
4. **Repository is the guardian** - it mediates between raw database rows and domain entities

## Best Practices

### ✅ DO: Use Repository for Application Code

```php
class UserService
{
    public function __construct(private Repository $userRepository) {}

    public function getActiveUsers(): array
    {
        $query = $this->userRepository->queryInstance()
            ->where('status = :status', ['status' => 'active']);

        return $this->userRepository->getIterator($query)->toEntities();
    }
}
```

### ❌ DON'T: Use Query Directly in Application Code

```php
// BAD: Mixing infrastructure and domain concerns
class UserService
{
    public function getActiveUsers(): array
    {
        $query = Query::getInstance()
            ->table('users')
            ->where('status = :status', ['status' => 'active']);

        $rows = $query->buildAndGetIterator(DatabaseExecutor::using($driver))->toArray();

        // Now you have to manually map arrays to entities!
        return array_map(fn($row) => new User($row), $rows);
    }
}
```

### ✅ DO: Use Query for Infrastructure Code

```php
// GOOD: Utility script for data export
class DataExporter
{
    public function exportToJson(string $table, string $filename): void
    {
        $query = Query::getInstance()->table($table);
        $rows = $query->buildAndGetIterator(DatabaseExecutor::using($driver))->toArray();

        file_put_contents($filename, json_encode($rows, JSON_PRETTY_PRINT));
    }
}
```

## Summary

| Aspect                 | Infrastructure Layer           | Domain Layer                                                |
|------------------------|--------------------------------|-------------------------------------------------------------|
| **Methods**            | `Query::buildAndGetIterator()` | `Repository::getIterator()`<br />`Repository::getByQuery()` |
| **Returns**            | Raw arrays                     | Domain entities                                             |
| **Mapper**             | Not required                   | Required                                                    |
| **Entity Transform**   | No                             | Yes (automatic)                                             |
| **Multi-Mapper JOINs** | Not supported                  | Supported                                                   |
| **Use Cases**          | Migrations, utilities, testing | Application logic, CRUD                                     |
| **Coupling**           | Low (stateless)                | High (repository lifecycle)                                 |

**Key Takeaway**: Use Repository methods in your application code. Reserve Query methods for infrastructure-level
operations where entity transformation is not needed or not desired.

## See Also

- [Repository Documentation](repository.md)
- [Querying the Database](querying-the-database.md)
- [Query Building](query-build.md)
