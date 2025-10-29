---
sidebar_position: 4
---

# Using Mapper Object

The Mapper object is the main object that you will use to interact with the database and your model.

## Why Use Mapper Objects?

### The Key Advantage: Decoupling

**The biggest advantage of using Mapper objects is decoupling your code from the database.** With Mapper, you can:

- ✅ **Work with legacy models** - Map existing classes without modifying them
- ✅ **Use third-party libraries** - Map models you don't own or can't change
- ✅ **Keep domain models clean** - No database annotations polluting your business logic
- ✅ **Change database schema independently** - Rename columns without touching your models
- ✅ **Support multiple databases** - Same model, different mappings for different databases

### When to Use Mapper Objects vs Attributes

| Approach                             | Use When                                            | Benefits                                     |
|--------------------------------------|-----------------------------------------------------|----------------------------------------------|
| **Attributes** (`#[FieldAttribute]`) | You control the model classes                       | Convenient, everything in one place          |
| **Mapper Objects**                   | Legacy code, third-party models, clean architecture | Complete decoupling, no model changes needed |

For the majority of cases, you can use attributes as shown in the [Getting Started](getting-started-model.md) section. This creates the mapper automatically.

## Use Cases

### Use Case 1: Legacy/Third-Party Code

Let's say you have a legacy class or a third-party library class that you **cannot modify**:

```php
<?php
// Legacy class from old codebase - YOU CANNOT CHANGE THIS
class Users
{
    public $id;
    public $name;
    public $createdate;
}
```

And your database has different column names:

```sql
CREATE TABLE user_accounts
(
    user_id    INT PRIMARY KEY,
    full_name  VARCHAR(255),
    created_at DATETIME
);
```

### Solution: External Mapping with Mapper Object

Instead of modifying the `Users` class (which you can't or don't want to do), you create an **external mapping**:

```php
<?php
use ByJG\MicroOrm\Mapper;
use ByJG\MicroOrm\FieldMapping;

// Create mapper externally - no changes to Users class needed!
$mapper = new Mapper(
    Users::class,        // Your legacy/third-party class
    'user_accounts',     // Actual database table name
    'user_id'            // Actual primary key column name
);

// Map properties to different column names
$mapper->addFieldMapping(
    FieldMapping::create('id')->withFieldName('user_id')
);
$mapper->addFieldMapping(
    FieldMapping::create('name')->withFieldName('full_name')
);
$mapper->addFieldMapping(
    FieldMapping::create('createdate')->withFieldName('created_at')
);
```

Now use the repository for CRUD operations:

```php
<?php
$dbDriver = \ByJG\AnyDataset\Db\Factory::getDbInstance('mysql://user:password@server/schema');
$executor = \ByJG\AnyDataset\Db\DatabaseExecutor::using($dbDriver);

$repository = new \ByJG\MicroOrm\Repository($executor, $mapper);

// Get data - automatically maps database columns to object properties
$user = $repository->get(10);
echo $user->name;  // ← Comes from 'full_name' column

// Save data - automatically maps object properties to database columns
$user->name = "New name";
$repository->save($user);  // ← Updates 'full_name' column

// Delete
$repository->delete($user);
```

**Key Point**: The `Users` class remains completely untouched - all mapping happens externally!

### Use Case 2: Clean Architecture with Pure Domain Models

One of the most powerful use cases for Mapper objects is implementing **Clean Architecture** where your domain models remain completely pure - no database concerns whatsoever.

#### Pure Domain Model (No Database Knowledge)

```php
<?php
namespace App\Domain\User;

// Pure domain model - no attributes, no ORM knowledge
class User
{
    private string $id;
    private string $email;
    private string $name;
    private \DateTimeImmutable $createdAt;

    public function __construct(string $id, string $email, string $name)
    {
        $this->id = $id;
        $this->email = $email;
        $this->name = $name;
        $this->createdAt = new \DateTimeImmutable();
    }

    // Pure business logic - no database concerns
    public function changeEmail(string $newEmail): void
    {
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email');
        }
        $this->email = $newEmail;
    }

    public function getId(): string { return $this->id; }
    public function getEmail(): string { return $this->email; }
    public function getName(): string { return $this->name; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
```

#### Infrastructure Layer Mapping (Separate from Domain)

```php
<?php
namespace App\Infrastructure\Persistence;

use ByJG\MicroOrm\Mapper;
use ByJG\MicroOrm\FieldMapping;
use ByJG\MicroOrm\Updatable;
use App\Domain\User\User;

// Mapping configuration lives in infrastructure layer
class UserMapper
{
    public static function create(): Mapper
    {
        $mapper = new Mapper(
            User::class,
            'users',
            'id'
        );

        // Map private properties (using getters/setters)
        $mapper->addFieldMapping(FieldMapping::create('id'));
        $mapper->addFieldMapping(FieldMapping::create('email'));
        $mapper->addFieldMapping(FieldMapping::create('name'));
        $mapper->addFieldMapping(
            FieldMapping::create('createdAt')
                ->withFieldName('created_at')
                // Transform DateTime to string for DB
                ->withUpdateFunction(fn($value) => $value->format('Y-m-d H:i:s'))
                // Transform string from DB to DateTime
                ->withSelectFunction(fn($value) => new \DateTimeImmutable($value))
        );

        return $mapper;
    }
}
```

#### Repository in Infrastructure Layer

```php
<?php
namespace App\Infrastructure\Persistence;

use ByJG\MicroOrm\Repository;
use ByJG\AnyDataset\Db\DatabaseExecutor;

class UserRepository
{
    private Repository $repository;

    public function __construct(DatabaseExecutor $executor)
    {
        $this->repository = new Repository($executor, UserMapper::create());
    }

    public function findById(string $id): ?User
    {
        return $this->repository->get($id);
    }

    public function save(User $user): void
    {
        $this->repository->save($user);
    }
}
```

**Benefits**: Pure domain model, testable without database, flexible to schema changes, clear architectural separation.

## Creating Mappers Reference

### Basic Mapper (Properties Match Column Names)

```php
<?php
// When property names match database column names
$mapper = new \ByJG\MicroOrm\Mapper(
    Users::class,   // Class name
    'users',        // Table name
    'id'            // Primary key
);

// No field mappings needed!
```

### Mapper with Field Mappings

```php
<?php
// When property names differ from column names
$mapper = new \ByJG\MicroOrm\Mapper(
    Users::class,
    'users',
    'id'
);

// Map property to different column name
$mapper->addFieldMapping(
    FieldMapping::create('createdate')->withFieldName('created')
);
```

## Summary

| Feature                 | Attributes               | Mapper Objects                          |
|-------------------------|--------------------------|-----------------------------------------|
| **Convenience**         | ✅ Very convenient        | ⚠️ More setup required                  |
| **Legacy Support**      | ❌ Need to modify classes | ✅ No modification needed                |
| **Clean Architecture**  | ❌ Couples domain to ORM  | ✅ Pure domain models                    |
| **Third-party Classes** | ❌ Can't add attributes   | ✅ Works perfectly                       |
| **Multiple Mappings**   | ❌ One mapping per class  | ✅ Different mappings possible           |
| **Best For**            | New projects you control | Legacy, third-party, clean architecture |

**Choose Mapper objects when you need complete decoupling between your domain models and database concerns.**


