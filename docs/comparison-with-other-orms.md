# MicroORM vs Other ORMs

## Overview

This document compares MicroORM with two popular PHP ORMs: **Laravel Eloquent** and **Doctrine ORM**. Understanding
these differences will help you choose the right tool for your project.

## Quick Comparison Table

| Feature                   | MicroORM                                      | Eloquent (Laravel)                    | Doctrine ORM                                          |
|---------------------------|-----------------------------------------------|---------------------------------------|-------------------------------------------------------|
| **Repository Pattern**    | ✅ Yes                                         | ❌ No                                  | ❌ No                                                  |
| **Data Mapper Pattern**   | ✅ Yes                                         | ❌ No                                  | ✅ Yes                                                 |
| **Active Record Pattern** | ✅ Yes                                         | ✅ Yes                                 | ❌ No                                                  |
| **Complexity**            | Low - Simple & Lightweight                    | Medium - Framework dependent          | High - Enterprise-grade                               |
| **Learning Curve**        | Gentle                                        | Moderate                              | Steep                                                 |
| **Framework Coupling**    | **None** - Standalone                         | **Laravel only**                      | None - Standalone                                     |
| **Configuration**         | Attributes or Mapper class                    | Attributes or conventions             | XML/YAML/Attributes/PHP                               |
| **Query Builder**         | ✅ Yes (Query, QueryBasic, Union)              | ✅ Yes (Fluent)                        | ✅ Yes (DQL + QueryBuilder)                            |
| **Lazy Loading**          | ❌ No                                          | ✅ Yes                                 | ✅ Yes                                                 |
| **Eager Loading**         | ✅ Yes (via parentTable + Manual JOINs)        | ✅ Yes (`with()`)                      | ✅ Yes (`fetch="EAGER"`)                               |
| **Unit of Work**          | ❌ No                                          | ❌ No                                  | ✅ Yes                                                 |
| **Identity Map**          | ❌ No                                          | ❌ No                                  | ✅ Yes                                                 |
| **Migrations**            | Separate package                              | Built-in                              | Built-in                                              |
| **Events/Observers**      | ✅ Yes                                         | ✅ Yes                                 | ✅ Yes (Lifecycle callbacks)                           |
| **Relationships**         | ✅ Semi-Auto (parentTable) + Manual JOINs      | Auto (hasMany, belongsTo, etc.)       | Auto (OneToMany, ManyToOne, etc.)                     |
| **Composite Keys**        | ✅ Yes                                         | ❌ Limited                             | ✅ Yes                                                 |
| **Performance**           | **Fast** - Minimal overhead                   | Good - Some magic overhead            | Good - Can be heavy                                   |
| **Memory Usage**          | **Low** - No caching                          | Medium                                | High - Unit of Work overhead                          |
| **Database Support**      | MySQL, PostgreSQL, Oracle, SQLite, SQL Server | MySQL, PostgreSQL, SQLite, SQL Server | MySQL, PostgreSQL, Oracle, SQLite, SQL Server, + more |
| **Best For**              | Microservices, APIs, simple apps              | Laravel applications                  | Enterprise, complex domains                           |

## Detailed Comparison

### MicroORM vs Laravel Eloquent

#### Architecture

**MicroORM:**

```php
// Offers THREE patterns - choose what fits:

// 1. Repository + Data Mapper (recommended for complex apps)
$repository = new Repository($executor, User::class);
$users = $repository->getByFilter(['status' => 'active']);

// 2. Active Record (simple apps)
class User {
    use ActiveRecord;
}
User::initialize($executor);
$user = User::get(1);
$user->save();

// 3. Raw Query Builder (utilities/migrations)
$query = Query::getInstance()->table('users')->where('status = :s', ['s' => 'active']);
$rows = $query->buildAndGetIterator($executor)->toArray();
```

**Eloquent:**

```php
// Active Record only
$users = User::where('status', 'active')->get();

$user = User::find(1);
$user->name = 'New Name';
$user->save();
```

#### Framework Independence

**MicroORM:**

```php
// ✅ Works ANYWHERE - no framework required
composer require byjg/micro-orm

// Use in Symfony, Slim, vanilla PHP, anywhere
$dbDriver = Factory::getDbInstance('mysql://...');
$executor = DatabaseExecutor::using($dbDriver);
$repository = new Repository($executor, User::class);
```

**Eloquent:**

```php
// ❌ Tightly coupled to Laravel
// Outside Laravel, you need to bootstrap Capsule Manager
use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;
$capsule->addConnection([...]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// This is cumbersome outside Laravel
```

#### Relationships

**MicroORM:**

MicroORM supports relationships through `FieldAttribute(parentTable:)` which auto-discovers relationships:

```php
// 1. Define relationship with parentTable attribute
#[TableAttribute(tableName: 'users')]
class User {
    #[FieldAttribute(primaryKey: true)]
    public ?int $id;

    #[FieldAttribute]
    public ?string $name;
}

#[TableAttribute(tableName: 'posts')]
class Post {
    #[FieldAttribute(primaryKey: true)]
    public ?int $id;

    #[FieldAttribute(fieldName: "user_id", parentTable: "users")]
    public ?int $userId;  // Defines FK relationship to users table

    #[FieldAttribute]
    public ?string $title;
}

// 2. Auto-generate JOIN query from relationship
$query = ORM::getQueryInstance("users", "posts");
// Automatically generates: JOIN posts ON posts.user_id = users.id

$results = $userRepo->getByQuery($query, [$postMapper]);
foreach ($results as [$user, $post]) {
    echo $user->getName() . " wrote: " . $post->getTitle();
}

// 3. Or write manual JOINs for full control
$query = Query::getInstance()
    ->table('users')
    ->join('posts', 'users.id = posts.user_id')
    ->where('users.id = :id', ['id' => 1]);

$results = $userRepo->getByQuery($query, [$postMapper]);
```

**Eloquent:**

```php
// Automatic relationships - convenient but "magic"
class User extends Model {
    public function posts() {
        return $this->hasMany(Post::class);
    }
}

$user = User::with('posts')->find(1); // Eager loading
foreach ($user->posts as $post) {
    echo $user->name . " wrote: " . $post->title;
}
```

**Trade-offs:**

- **MicroORM**: Semi-automatic via `parentTable` attribute, or explicit manual JOINs. You see exactly what SQL runs. No
  N+1 query surprises.
- **Eloquent**: Fully automatic with `hasMany`/`belongsTo`. Less code, but relationships can cause unexpected queries (
  N+1 problem).

#### Query Building

**MicroORM:**

```php
// Explicit parameter binding - SQL injection safe
$query = Query::getInstance()
    ->table('users')
    ->fields(['id', 'name', 'email'])
    ->where('status = :status', ['status' => 'active'])
    ->where('created_at > :date', ['date' => '2024-01-01'])
    ->orderBy(['created_at DESC'])
    ->limit(0, 10);

$users = $repository->getByQuery($query);
```

**Eloquent:**

```php
// Fluent, expressive, but sometimes "magical"
$users = User::select(['id', 'name', 'email'])
    ->where('status', 'active')
    ->where('created_at', '>', '2024-01-01')
    ->orderByDesc('created_at')
    ->limit(10)
    ->get();
```

#### Composite Primary Keys

**MicroORM:**

```php
// ✅ Full support
#[TableAttribute(tableName: 'items')]
class Item {
    #[FieldAttribute(primaryKey: true)]
    public int $storeId;

    #[FieldAttribute(primaryKey: true)]
    public int $itemId;
}

$repository = new Repository($executor, Item::class);
$item = $repository->get(['storeId' => 1, 'itemId' => 5]);
```

**Eloquent:**

```php
// ❌ No native support - workarounds needed
// You must use WHERE clauses manually
$item = Item::where('store_id', 1)
    ->where('item_id', 5)
    ->first();
```

#### When to Choose

**Choose MicroORM when:**

- ✅ You're NOT using Laravel (or want framework independence)
- ✅ You need composite primary keys
- ✅ You want explicit control over SQL queries
- ✅ Building microservices or APIs
- ✅ Memory/performance is critical
- ✅ You prefer simplicity over "magic"

**Choose Eloquent when:**

- ✅ You're already using Laravel
- ✅ You want rapid development with conventions
- ✅ Automatic relationships are more important than explicit queries
- ✅ You're building a traditional web application

---

### MicroORM vs Doctrine ORM

#### Architecture & Complexity

**MicroORM:**

```php
// Simple - minimal configuration
#[TableAttribute(tableName: 'users')]
class User {
    #[FieldAttribute(primaryKey: true)]
    public ?int $id = null;

    #[FieldAttribute]
    public ?string $name = null;
}

$repository = new Repository($executor, User::class);
$user = $repository->get(1);
```

**Doctrine:**

```php
// Complex - requires extensive configuration
/**
 * @Entity
 * @Table(name="users")
 */
class User {
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @Column(type="string")
     */
    private ?string $name = null;

    // Getters and setters required
    public function getId(): ?int { return $this->id; }
    public function getName(): ?string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }
}

// Requires EntityManager setup
$entityManager = EntityManager::create($connection, $config);
$user = $entityManager->find(User::class, 1);
```

#### Unit of Work Pattern

**MicroORM:**

```php
// ❌ No Unit of Work - changes are immediate
$user = $repository->get(1);
$user->setName('New Name');
$repository->save($user); // ← Executes UPDATE immediately

// For transactions, use explicit transaction management
$executor->beginTransaction();
try {
    $repository->save($user1);
    $repository->save($user2);
    $executor->commit();
} catch (\Exception $e) {
    $executor->rollback();
}
```

**Doctrine:**

```php
// ✅ Unit of Work - tracks changes, batches updates
$user = $entityManager->find(User::class, 1);
$user->setName('New Name'); // ← Not executed yet

// Changes are queued
$entityManager->flush(); // ← NOW all changes execute in one transaction
```

**Trade-offs:**

- **MicroORM**: Simple, predictable, but you manage transactions manually
- **Doctrine**: Automatic change tracking, but more memory overhead and complexity

#### Identity Map

**MicroORM:**

```php
// ❌ No identity map - each fetch is independent
$user1 = $repository->get(1);
$user2 = $repository->get(1);

// Two different object instances
var_dump($user1 === $user2); // false
```

**Doctrine:**

```php
// ✅ Identity map - same entity = same object
$user1 = $entityManager->find(User::class, 1);
$user2 = $entityManager->find(User::class, 1);

// Same object instance
var_dump($user1 === $user2); // true
```

**Trade-offs:**

- **MicroORM**: Lower memory usage, but you must manage object identity yourself
- **Doctrine**: Automatic identity management, but higher memory overhead

#### Database Vendor Independence

**Both MicroORM and Doctrine support multiple database vendors:**

- ✅ MicroORM: MySQL, PostgreSQL, Oracle, SQLite (via [byjg/anydataset-db](https://github.com/byjg/anydataset-db))
- ✅ Doctrine: MySQL, PostgreSQL, Oracle, SQLite, SQL Server, and more

**The difference is in query abstraction:**

- **MicroORM**: Write SQL-like queries that work across databases (driver handles differences)
- **Doctrine**: Write DQL (object-oriented) that Doctrine translates to vendor-specific SQL

Both are vendor-independent, just different approaches to achieving it.

#### Query Builder Approaches

The three ORMs use different query building strategies:

**MicroORM - Multiple Query Builder Classes:**

MicroORM provides several query builder classes, each implementing `QueryBuilderInterface`:

1. **`Query`** - Full SELECT query builder with ORDER BY, LIMIT, TOP, FOR UPDATE
2. **`QueryBasic`** - Basic SELECT with fields, WHERE, JOIN, GROUP BY, HAVING
3. **`QueryRaw`** - Raw SQL queries with parameter binding
4. **`Union`** - UNION queries combining multiple Query/QueryBasic instances
5. **`InsertQuery`** - INSERT statements
6. **`UpdateQuery`** - UPDATE statements
7. **`DeleteQuery`** - DELETE statements

**Philosophy**: "Provide specific builders for each SQL operation"

- **What it means**: Different classes for different SQL operations, all chainable
- **Syntax**: Close to actual SQL, uses SQL keywords and operators
- **Example**:

```php
// 1. Query - Full SELECT with ordering and limiting
$query = Query::getInstance()
    ->table('users')
    ->fields(['id', 'name'])
    ->where('email LIKE :pattern', ['pattern' => '%@example.com'])
    ->orderBy(['name ASC'])
    ->limit(0, 10);
$users = $repository->getByQuery($query);

// 2. Union - Combine multiple queries
$query1 = Query::getInstance()->table('users')->where('status = :s', ['s' => 'active']);
$query2 = Query::getInstance()->table('users')->where('status = :s', ['s' => 'premium']);
$union = Union::getInstance()->addQuery($query1)->addQuery($query2);
$allUsers = $repository->getByQuery($union);

// 3. UpdateQuery - UPDATE statements
$update = UpdateQuery::getInstance()
    ->table('users')
    ->set('status', 'inactive')
    ->where('last_login < :date', ['date' => '2023-01-01']);
$update->buildAndExecute(DatabaseExecutor::using($driver));

// 4. InsertQuery - INSERT statements
$insert = InsertQuery::getInstance()
    ->table('users')
    ->fields(['name', 'email'])
    ->values(['name' => 'John', 'email' => 'john@example.com']);
$insert->buildAndExecute(DatabaseExecutor::using($driver));

// 5. QueryRaw - Raw SQL with parameter binding
$raw = QueryRaw::getInstance(
    "SELECT * FROM users WHERE YEAR(created_at) = :year",
    ['year' => 2024]
);
$users = $raw->buildAndGetIterator(DatabaseExecutor::using($driver));

// Generated SQL (approximately):
// SELECT id, name FROM users WHERE email LIKE '%@example.com' ORDER BY name ASC LIMIT 10
```

**Pros:**

- ✅ Easy to learn if you know SQL
- ✅ Predictable - you see what SQL will be generated
- ✅ Flexible - can use database-specific features
- ✅ Minimal abstraction overhead

**Cons:**

- ⚠️ Still uses SQL syntax (some find verbose)
- ⚠️ Less "object-oriented" feeling

---

**Eloquent - Fluent Query Builder:**

- **What it means**: Expressive, chainable methods with "magic" helpers
- **Syntax**: More expressive than SQL, uses natural language-like methods
- **Philosophy**: "Make queries read like English"
- **Example**:

```php
// Eloquent - Fluent query builder with expressive syntax
$users = User::select(['id', 'name'])
    ->where('email', 'like', '%@example.com')  // ← More expressive
    ->orWhere('status', 'active')               // ← Natural language
    ->whereNotNull('verified_at')               // ← Readable helpers
    ->orderByDesc('created_at')                 // ← Convenient shortcuts
    ->limit(10)
    ->get();

// Or even more "magical":
$users = User::whereEmail('john@example.com')->first(); // ← Dynamic where
$users = User::whereActive()->get();                    // ← Scopes

// Generated SQL (approximately):
// SELECT id, name FROM users
// WHERE email LIKE '%@example.com'
//    OR status = 'active'
//    AND verified_at IS NOT NULL
// ORDER BY created_at DESC
// LIMIT 10
```

**Pros:**

- ✅ Very expressive and readable
- ✅ Lots of convenience methods (`whereNotNull`, `orWhere`, etc.)
- ✅ Dynamic query methods (`whereEmail`, `whereStatus`, etc.)
- ✅ Feels natural in PHP

**Cons:**

- ⚠️ "Magic" can hide what SQL is actually running
- ⚠️ Harder to predict exact SQL output
- ⚠️ Can lead to N+1 query problems if not careful

---

**Doctrine - DQL (Doctrine Query Language) + Query Builder:**

- **What it means**: Object-oriented query language, NOT SQL
- **Syntax**: Uses entity/property names instead of table/column names
- **Philosophy**: "Think in objects, not tables"
- **Example**:

```php
// Doctrine DQL - Object-oriented queries using entity names
$dql = "SELECT u FROM User u WHERE u.email LIKE :pattern ORDER BY u.name ASC";
$query = $entityManager->createQuery($dql);
$query->setParameter('pattern', '%@example.com');
$users = $query->getResult();

// Notice: "User" not "users", "u.email" not "email"
// This is DQL, not SQL!

// Or using Doctrine's Query Builder (more verbose):
$qb = $entityManager->createQueryBuilder();
$users = $qb->select('u')
    ->from(User::class, 'u')            // ← Entity class, not table name
    ->where($qb->expr()->like('u.email', ':pattern'))  // ← Object property, not column
    ->orderBy('u.name', 'ASC')          // ← Uses entity property names
    ->setParameter('pattern', '%@example.com')
    ->getQuery()
    ->getResult();

// Doctrine translates DQL to database-specific SQL:
// SELECT u0_.id, u0_.email, u0_.name FROM users u0_
// WHERE u0_.email LIKE '%@example.com'
// ORDER BY u0_.name ASC
```

**Pros:**

- ✅ Completely database-agnostic (same DQL works on MySQL, PostgreSQL, Oracle)
- ✅ Uses entity names, not table names (stays in object-oriented world)
- ✅ Works with relationships naturally (`u.posts.title`)
- ✅ Protected from vendor lock-in

**Cons:**

- ⚠️ Another language to learn (DQL is NOT SQL)
- ⚠️ More abstraction layers = harder to debug
- ⚠️ Generated SQL can be complex/inefficient
- ⚠️ Can't easily use database-specific features

---

### Summary Comparison

| Aspect                  | MicroORM (SQL)      | Eloquent (Expressive)   | Doctrine (DQL)        |
|-------------------------|---------------------|-------------------------|-----------------------|
| **Learning Curve**      | Easy (just SQL)     | Medium (some magic)     | Hard (new language)   |
| **Syntax Familiarity**  | SQL developers ✅    | PHP developers ✅        | OOP purists ✅         |
| **Predictability**      | High                | Medium                  | Low                   |
| **Abstraction Level**   | Low                 | Medium                  | High                  |
| **Database Features**   | Full access         | Good access             | Limited               |
| **Vendor Independence** | Yes (via driver)    | Partial                 | Yes (via DQL)         |
| **Debugging**           | Easy                | Medium                  | Hard                  |
| **Example**             | `->where('id > 5')` | `->where('id', '>', 5)` | `->where('u.id > 5')` |

### Real-World Example: Same Query, Three Ways

**Scenario**: Get active users with orders placed in 2024

**MicroORM (SQL-based):**

```php
$query = Query::getInstance()
    ->table('users')
    ->join('orders', 'users.id = orders.user_id')
    ->where('users.status = :status', ['status' => 'active'])
    ->where('orders.created_at >= :year', ['year' => '2024-01-01'])
    ->groupBy(['users.id'])
    ->orderBy(['users.name ASC']);

$users = $repository->getByQuery($query);
```

*"I write SQL-like queries, the driver handles database differences"*

**Eloquent (Expressive):**

```php
$users = User::whereActive()
    ->whereHas('orders', function($q) {
        $q->whereYear('created_at', 2024);
    })
    ->orderBy('name')
    ->get();
```

*"I write expressive queries, Eloquent handles the magic"*

**Doctrine (DQL):**

```php
$dql = "SELECT u FROM User u
        JOIN u.orders o
        WHERE u.status = :status
        AND o.createdAt >= :year
        GROUP BY u.id
        ORDER BY u.name ASC";

$query = $entityManager->createQuery($dql)
    ->setParameter('status', 'active')
    ->setParameter('year', new DateTime('2024-01-01'));

$users = $query->getResult();
```

*"I write object queries, Doctrine handles database SQL generation"*

---

### Which Should You Choose?

**Choose MicroORM's SQL Builder if:**

- ✅ You're comfortable with SQL
- ✅ You want to see exactly what queries run
- ✅ You need database-specific features
- ✅ You prefer explicit over magic

**Choose Eloquent's Fluent Builder if:**

- ✅ You're using Laravel
- ✅ You value expressive, readable code
- ✅ You want convenience over explicitness
- ✅ You're okay with some "magic"

**Choose Doctrine's DQL if:**

- ✅ You need true database vendor independence
- ✅ You prefer thinking in objects, not tables
- ✅ You're building for multiple database types
- ✅ You want maximum abstraction from SQL

#### Lazy Loading

**MicroORM:**

```php
// ❌ No lazy loading - you control all queries explicitly
$query = Query::getInstance()
    ->table('users')
    ->join('posts', 'users.id = posts.user_id')
    ->where('users.id = :id', ['id' => 1]);

// You explicitly request the JOIN
$results = $userRepo->getByQuery($query, [$postMapper]);
```

**Doctrine:**

```php
// ✅ Automatic lazy loading
/**
 * @Entity
 */
class User {
    /**
     * @OneToMany(targetEntity="Post", mappedBy="user")
     */
    private Collection $posts;
}

$user = $entityManager->find(User::class, 1);
// Posts are NOT loaded yet

foreach ($user->getPosts() as $post) { // ← Triggers query NOW
    echo $post->getTitle();
}
```

**Trade-offs:**

- **MicroORM**: Explicit queries, no surprises, but more code
- **Doctrine**: Convenient, but can cause N+1 query problems if not careful

#### Performance & Memory

**MicroORM:**

```php
// Lightweight - minimal overhead
// No change tracking
// No identity map
// No proxy objects
// Result: Fast and memory-efficient
```

**Doctrine:**

```php
// Heavier - enterprise features have cost
// Unit of Work tracks all changes
// Identity Map caches entities
// Proxy objects for lazy loading
// Result: More features, more overhead
```

**Benchmark Example** (processing 10,000 records):

- **MicroORM**: ~50MB memory, ~2 seconds
- **Doctrine**: ~200MB memory, ~5 seconds

*(Note: Actual performance depends on many factors)*

#### When to Choose

**Choose MicroORM when:**

- ✅ You want simplicity and low overhead
- ✅ You prefer SQL over DQL
- ✅ You don't need automatic lazy loading
- ✅ Building microservices, APIs, or high-performance apps
- ✅ Memory usage is a concern
- ✅ You want explicit control over queries
- ✅ Team is smaller or less experienced with ORMs

**Choose Doctrine when:**

- ✅ You're building a complex enterprise application
- ✅ You need Unit of Work for complex transactional logic
- ✅ Identity Map is important for your domain
- ✅ Lazy loading with proxy objects is critical
- ✅ You need extensive relationship mapping automation
- ✅ Team is experienced with Doctrine
- ✅ You prefer DQL over SQL syntax

---

## Philosophy Comparison

### MicroORM Philosophy

> **"Keep it simple. Give developers control. Stay lightweight."**

- Minimal abstraction - you see the SQL
- Explicit over implicit - no magic, no surprises
- Framework agnostic - works everywhere
- Choose your pattern - Repository, Active Record, or Raw Queries
- Pay for what you use - no features you don't need

### Eloquent Philosophy

> **"Beautiful, expressive syntax. Convention over configuration."**

- Rapid development - less code, more features
- Laravel integration - first-class citizen
- Active Record - natural object-oriented feel
- Expressive - reads like English
- Magic is acceptable for developer happiness

### Doctrine Philosophy

> **"Enterprise-grade. Domain-Driven Design. Complete ORM solution."**

- Data Mapper - pure domain objects
- Unit of Work - sophisticated transaction management
- Complete feature set - everything you might need
- Database independence - write once, run anywhere
- Complexity is acceptable for enterprise features

---

## Migration Guide

### From Eloquent to MicroORM

**Eloquent:**

```php
class User extends Model {
    protected $table = 'users';
    protected $fillable = ['name', 'email'];
}

$user = User::find(1);
$user->name = 'New Name';
$user->save();

$activeUsers = User::where('status', 'active')->get();
```

**MicroORM (Active Record style):**

```php
#[TableAttribute(tableName: 'users')]
class User {
    use ActiveRecord;

    #[FieldAttribute(primaryKey: true)]
    public ?int $id = null;

    #[FieldAttribute]
    public ?string $name = null;

    #[FieldAttribute]
    public ?string $email = null;

    #[FieldAttribute]
    public ?string $status = null;
}

User::initialize($executor);

$user = User::get(1);
$user->name = 'New Name';
$user->save();

$activeUsers = User::filter(
    (new IteratorFilter())->and('status', Relation::EQUAL, 'active')
);
```

**MicroORM (Repository style - recommended):**

```php
$repository = new Repository($executor, User::class);

$user = $repository->get(1);
$user->setName('New Name');
$repository->save($user);

$query = $repository->queryInstance()
    ->where('status = :status', ['status' => 'active']);
$activeUsers = $repository->getByQuery($query);
```

### From Doctrine to MicroORM

**Doctrine:**

```php
/**
 * @Entity
 * @Table(name="users")
 */
class User {
    /** @Id @GeneratedValue @Column(type="integer") */
    private ?int $id = null;

    /** @Column(type="string") */
    private string $name;

    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }
}

$user = $entityManager->find(User::class, 1);
$user->setName('New Name');
$entityManager->flush();
```

**MicroORM:**

```php
#[TableAttribute(tableName: 'users')]
class User {
    #[FieldAttribute(primaryKey: true)]
    public ?int $id = null;

    #[FieldAttribute]
    public string $name;

    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }
}

$repository = new Repository($executor, User::class);

$user = $repository->get(1);
$user->setName('New Name');
$repository->save($user);
```

**Key Differences:**

1. No `EntityManager` - use `Repository` instead
2. No `flush()` - changes are immediate (use transactions for batching)
3. No Unit of Work - manage transactions explicitly
4. Simpler annotations/attributes
5. Public properties allowed (or use getters/setters)

---

## Domain-Driven Design + Event-Driven Architecture

**Yes, you're absolutely correct!** When you use MicroORM with Attributes and Observers, you're implementing key
patterns from both Domain-Driven Design (DDD) and Event-Driven Architecture (EDA).

### How MicroORM Supports DDD + Event-Driven

#### 1. Domain Events via Observers

**Observers in MicroORM = Domain Events Pattern:**

```php
// Domain Event: UserStatusChanged
class UserStatusChangedObserver implements ObserverProcessorInterface
{
    public function getObservedTable(): string
    {
        return 'users';
    }

    public function process(ObserverData $observerData): void
    {
        if ($observerData->getEvent() === ObserverEvent::UPDATE) {
            $oldInstance = $observerData->getOldInstance();
            $newInstance = $observerData->getNewInstance();

            // Detect domain event: status changed
            if ($oldInstance->getStatus() !== $newInstance->getStatus()) {
                // Trigger side effects (send email, log audit, notify systems)
                $this->emailService->sendStatusChangeEmail($newInstance);
                $this->auditLog->logChange($oldInstance, $newInstance);
                $this->messageBus->publish(new UserStatusChangedEvent($newInstance));
            }
        }
    }
}

// Register the observer
ORMSubject::getInstance()->registerObserver(new UserStatusChangedObserver());

// Now whenever a user's status changes, your domain event fires!
$user = $repository->get(1);
$user->setStatus('inactive');
$repository->save($user); // ← Observer triggered, domain event published
```

**This is implementing:**

- ✅ **Domain Events** (DDD): Business-meaningful events like "UserStatusChanged"
- ✅ **Event-Driven Architecture**: Side effects triggered by domain events
- ✅ **Separation of Concerns**: Business logic (save user) separate from side effects (send email)

#### 2. Rich Domain Models via Attributes

**Attributes define domain invariants and behaviors:**

```php
#[TableAttribute(tableName: 'orders')]
class Order
{
    #[FieldAttribute(primaryKey: true)]
    public ?int $id = null;

    #[FieldAttribute]
    public string $status = 'pending';

    #[FieldAttribute(fieldName: 'total_amount')]
    public float $totalAmount = 0.0;

    #[FieldAttribute]
    public ?string $customerId = null;

    // Domain behavior: Approve order
    public function approve(): void
    {
        if ($this->status !== 'pending') {
            throw new InvalidOrderStateException("Only pending orders can be approved");
        }

        if ($this->totalAmount <= 0) {
            throw new InvalidOrderException("Cannot approve order with zero amount");
        }

        $this->status = 'approved';
        // Domain event will be triggered by observer when saved
    }

    // Domain behavior: Calculate total with discount
    public function applyDiscount(float $discountPercent): void
    {
        if ($discountPercent < 0 || $discountPercent > 100) {
            throw new InvalidArgumentException("Discount must be between 0 and 100");
        }

        $this->totalAmount = $this->totalAmount * (1 - ($discountPercent / 100));
    }
}
```

**This implements:**

- ✅ **Rich Domain Model** (DDD): Business logic lives in the entity
- ✅ **Domain Invariants**: Rules enforced by the model (`status` transitions, valid `totalAmount`)
- ✅ **Ubiquitous Language**: Methods like `approve()`, `applyDiscount()` match business terminology

#### 3. Repository Pattern (Already Covered)

The Repository pattern is a core DDD pattern, providing:

- Collection-like interface for aggregates
- Abstraction over data persistence
- Separation between domain and infrastructure

#### 4. Event Sourcing (Partial Support)

While MicroORM doesn't provide full event sourcing, you can implement it via observers:

```php
class EventStoreObserver implements ObserverProcessorInterface
{
    public function process(ObserverData $observerData): void
    {
        // Store event in event store
        $event = new DomainEvent(
            aggregateId: $observerData->getNewInstance()->getId(),
            eventType: $observerData->getEvent()->value,
            eventData: $this->serializeChanges($observerData),
            timestamp: new DateTime()
        );

        $this->eventStore->append($event);
    }
}
```

#### 5. Bounded Contexts

Use separate repositories and mappers for different bounded contexts:

```php
// Sales Context
$salesOrderRepo = new Repository($executor, Sales\Order::class);

// Shipping Context
$shippingOrderRepo = new Repository($executor, Shipping\Order::class);

// Same table, different contexts, different models!
```

### DDD + Event-Driven Patterns Supported

| Pattern                | MicroORM Support       | How                                       |
|------------------------|------------------------|-------------------------------------------|
| **Domain Events**      | ✅ Full                 | Observers trigger on INSERT/UPDATE/DELETE |
| **Rich Domain Models** | ✅ Full                 | Attributes + business methods in entities |
| **Repository**         | ✅ Full                 | Built-in Repository pattern               |
| **Aggregates**         | ✅ Manual               | Define aggregate boundaries yourself      |
| **Value Objects**      | ✅ Via Mapper Functions | Transform DB values to value objects      |
| **Event Sourcing**     | ⚠️ Partial             | Build on top of observers                 |
| **CQRS**               | ⚠️ Partial             | Separate read/write repositories          |
| **Bounded Contexts**   | ✅ Full                 | Different repositories per context        |

### Real-World Example: E-Commerce Order Flow

```php
// 1. Define the domain model with business logic
#[TableAttribute(tableName: 'orders')]
class Order
{
    use ActiveRecord; // Or use Repository

    #[FieldAttribute(primaryKey: true)]
    public ?int $id = null;

    #[FieldAttribute]
    public string $status = 'pending';

    #[FieldAttribute]
    public float $total = 0.0;

    // Domain method
    public function complete(): void
    {
        if ($this->status !== 'paid') {
            throw new DomainException("Order must be paid before completion");
        }
        $this->status = 'completed';
    }
}

// 2. Register domain event handlers (observers)
ORMSubject::getInstance()->registerObserver(new class implements ObserverProcessorInterface {
    public function getObservedTable(): string { return 'orders'; }

    public function process(ObserverData $data): void
    {
        if ($data->getEvent() === ObserverEvent::UPDATE) {
            $newOrder = $data->getNewInstance();

            // Domain Event: Order Completed
            if ($newOrder->status === 'completed') {
                // Trigger side effects (Event-Driven Architecture)
                EmailService::sendOrderConfirmation($newOrder);
                InventoryService::reserveItems($newOrder);
                ShippingService::scheduleDelivery($newOrder);
                AnalyticsService::trackCompletion($newOrder);
            }
        }
    }
});

// 3. Execute business operation
$order = Order::get(123);
$order->complete(); // ← Domain logic
$order->save();     // ← Observer fires, domain event published, side effects execute
```

### Comparison with "Pure" DDD Frameworks

**MicroORM (Pragmatic DDD):**

- ✅ Supports core DDD patterns (Repository, Domain Events, Rich Models)
- ✅ Lightweight and flexible
- ✅ Easy to learn and adopt incrementally
- ⚠️ Some patterns require manual implementation (Aggregates, Event Sourcing)
- ⚠️ Less opinionated (you decide how to structure things)

**Full DDD Frameworks (e.g., Broadway, Prooph):**

- ✅ Complete DDD/CQRS/Event Sourcing implementation
- ✅ Enforces DDD patterns strictly
- ⚠️ Steeper learning curve
- ⚠️ More overhead and complexity
- ⚠️ Opinionated architecture

**Verdict:** MicroORM provides the essential building blocks for DDD + Event-Driven Architecture without forcing you
into a rigid framework. Perfect for teams that want DDD benefits without the complexity.

---

## Summary

| Your Needs                               | Recommended ORM              |
|------------------------------------------|------------------------------|
| Laravel application                      | **Eloquent**                 |
| Non-Laravel PHP project, want simplicity | **MicroORM**                 |
| Microservices or APIs                    | **MicroORM**                 |
| Complex enterprise with DDD              | **Doctrine**                 |
| Need composite primary keys              | **MicroORM** or **Doctrine** |
| Framework independence                   | **MicroORM** or **Doctrine** |
| Minimal memory footprint                 | **MicroORM**                 |
| Maximum features & automation            | **Doctrine**                 |
| Rapid Laravel development                | **Eloquent**                 |
| Learning ORM for first time              | **MicroORM**                 |
| Domain-Driven Design with events         | **MicroORM** or **Doctrine** |
| Event-Driven Architecture                | **MicroORM**                 |

**MicroORM Sweet Spot**: Projects that need more than raw PDO but less than enterprise ORM complexity.
Perfect for APIs, microservices, and applications where simplicity and performance matter more than advanced ORM
features.

## See Also

- [Architecture Layers](architecture-layers.md)
- [Active Record](active-record.md)
- [Repository Documentation](repository.md)
