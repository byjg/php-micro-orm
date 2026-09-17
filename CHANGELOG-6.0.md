# Changelog - Version 6.0

## Overview

Version 6.0 is a major release that introduces significant improvements to type safety, adds new features for enhanced ORM functionality, and removes deprecated methods from version 5.x. This release requires PHP 8.3+ and includes breaking changes that require code updates when upgrading from 5.x.

## New Features

### ActiveRecordQuery - Fluent Query API
- Introduced `ActiveRecordQuery` for a fluent and chainable query API
- Provides an intuitive way to build complex queries with method chaining
- Enhances the Active Record pattern implementation
- See: `docs/active-record.md` for examples

### UUID Support Enhancements
- Added comprehensive UUID field support beyond just primary keys
- Introduced `FormatSelectUuidMapper` and `FormatUpdateUuidMapper` (renamed from Binary variants)
- Added `HexUuidMapperFunctionTest` for validation of UUID handling
- Full support for UUIDs in regular fields with automatic formatting
- See: `docs/uuid-support.md`

### Varchar Primary Keys
- Added support for non-integer (varchar) primary keys
- Introduced `Product` and `ActiveRecordProduct` models showcasing custom primary key handling
- Enhanced primary key validation and handling across the ORM
- Updated tests and examples with seed functions for varchar PKs

### HAVING Clause Support
- Added native support for HAVING clause in query building
- Allows for aggregate function filtering in SQL queries
- Integrates seamlessly with the existing query API

### Entity Lifecycle Hooks
- Added `beforeUpdate` and `beforeInsert` hooks to the `TableAttribute`
- Introduced `EntityProcessorInterface` for custom entity processing
- Allows for custom validation and data transformation before persistence
- Can be defined at both table attribute and mapper levels
- Methods: `withBeforeInsert()` and `withBeforeUpdate()` in Mapper

### Mapper Functions (Enhanced)
- Introduced `MapperFunctionInterface` as the standard interface for field transformations
- Added comprehensive mapper functions:
  - `StandardMapper` - basic field mapping
  - `ReadOnlyMapper` - read-only fields
  - `NowUtcMapper` - automatic UTC timestamp
  - `FormatUpdateUuidMapper` - UUID formatting on update
  - `FormatSelectUuidMapper` - UUID formatting on select
- Simplified and more consistent API for field transformations
- See: `docs/mapper-functions.md`

### Enhanced Field Mapping
- Added `MapperTest` to validate field mapping behavior
- Support for field overwriting, aliases, and non-existent properties
- Added `getPropertyName()` method to Mapper for reverse field-to-property lookup
- Improved handling of array-to-object casting in Mapper

### Observer Events
- Introduced `ObserverEvent` enum with Insert, Update, and Delete events
- Provides type-safe event handling for database operations
- See: `docs/observers.md`

### Update Constraints
- Added `UpdateConstraintInterface` for controlling update operations
- Introduced `CustomConstraint` for custom update validation
- Added `RequireChangedValuesConstraint` to ensure values have changed before update
- New exception: `RequireChangedValuesConstraintException`
- See: `docs/update-constraints.md`

### Enhanced Primary Key Handling
- Improved validation with `MissingPrimaryKeyException`
- More robust primary key validation in `Repository::getPrimaryKeys()`
- Better error messages for missing or invalid primary keys
- Enhanced support for composite primary keys

### Iterator Support
- Added `buildAndExecuteIterator()` to `QueryBuilderInterface`
- Implemented `getIterator()` and enhanced `getByQuery()` in Repository
- Allows for efficient memory usage with large result sets

### Serialization Optimization
- Applied `withStopAtFirstLevel()` in Serialize calls across multiple classes
- Prevents unnecessary deep parsing and improves performance
- Optimizes data transfer between layers

### Documentation Improvements
- Added comprehensive `docs/architecture-layers.md` explaining Infrastructure vs Domain layers
- Added `docs/comparison-with-other-orms.md` comparing with Eloquent and Doctrine
- Added `docs/common-traits.md` for timestamp field helpers
- Added `docs/mapper-functions.md` for field transformation documentation
- Added `docs/query-build.md` for SQL query building
- Expanded and improved all existing documentation files
- Better examples and code samples throughout

## Bug Fixes

- Fixed `Repository::setBeforeUpdate()` and `Repository::setBeforeInsert()` implementations
- Improved primary key handling edge cases
- Fixed namespace references for `byjg/anydataset-db` compatibility
- Resolved Psalm static analysis issues
- Fixed test compatibility with updated dependencies
- Corrected return type for `Repository::insert()` to `?int`
- Improved transaction handling in Repository
- Fixed UUID formatting consistency issues

## Breaking Changes

| Before (5.x) | After (6.0) | Description |
|--------------|-------------|-------------|
| `php: >=8.1 <8.4` | `php: >=8.3 <8.6` | PHP 8.3+ is now required |
| `byjg/anydataset-db: ^5.0` | `byjg/anydataset-db: ^6.0` | Updated to version 6.x of anydataset-db |
| `phpunit/phpunit: ^9.6` | `phpunit/phpunit: ^10.5\|^11.5` | Updated to PHPUnit 10.5+ or 11.5+ |
| `byjg/cache-engine: ^5.0` | `byjg/cache-engine: ^6.0` | Updated to version 6.x |
| `DbFunctionsInterface` | `DatabaseExecutor` | Interface renamed for clarity |
| `DbDriverInterface` parameter | `Repository->getExecutor()` | Access database executor through repository |
| `SqlObject` | `SqlStatement` | Class renamed across entire codebase |
| `UniqueIdGeneratorInterface` | `MapperFunctionInterface` | Interface renamed for consistency |
| `SelectBinaryUuidMapper` | `FormatSelectUuidMapper` | Mapper class renamed |
| `UpdateBinaryUuidMapper` | `FormatUpdateUuidMapper` | Mapper class renamed |
| `Mapper::addFieldMap()` | `Mapper::addFieldMapping()` | Deprecated method removed |
| `MapperClosure` | Mapper Functions (e.g., `StandardMapper`) | Deprecated class removed |
| `AllowOnlyNewValuesConstraintException` | Removed/replaced | Exception removed |
| `Literal` class usage | `LiteralInterface` | Refactored to use interface |
| Nullable types (inconsistent) | Strict nullable types (`?Type`) | Improved type safety across all methods |
| `withPrimaryKeySeedFunction(callable)` | `withPrimaryKeySeedFunction(string\|MapperFunctionInterface)` | Type signature changed |

### Removed Deprecated Features

The following deprecated features from 5.x have been removed:

1. **`Mapper::addFieldMap()`** - Use `Mapper::addFieldMapping()` instead
2. **`MapperClosure`** - Use the new Mapper Functions classes (`StandardMapper`, `ReadOnlyMapper`, etc.)
3. **Old closure-based field transformations** - Use `MapperFunctionInterface` implementations
4. **Direct `DbDriverInterface` access** - Use `Repository->getExecutor()` instead

## Upgrade Path from 5.x to 6.0

### Step 1: Update Dependencies

Update your `composer.json`:

```json
{
  "require": {
    "php": ">=8.3",
    "byjg/micro-orm": "^6.0"
  }
}
```

Run:
```bash
composer update byjg/micro-orm
```

### Step 2: Update PHP Version

Ensure your project is running PHP 8.3 or higher. Version 6.0 takes advantage of PHP 8.3+ features including:
- Typed class constants
- Enhanced type system
- Improved readonly properties
- Better attribute handling

### Step 3: Replace Deprecated Classes and Methods

#### Replace `SqlObject` with `SqlStatement`

**Before:**
```php
use ByJG\AnyDataset\Db\SqlObject;

$sql = new SqlObject('SELECT * FROM users');
```

**After:**
```php
use ByJG\AnyDataset\Db\SqlStatement;

$sql = new SqlStatement('SELECT * FROM users');
```

#### Replace `DbFunctionsInterface` with `DatabaseExecutor`

**Before:**
```php
public function __construct(DbFunctionsInterface $dbFunctions)
{
    $this->dbFunctions = $dbFunctions;
}
```

**After:**
```php
public function __construct(DatabaseExecutor $executor)
{
    $this->executor = $executor;
}
```

Or, if you were accessing it through Repository:

**Before:**
```php
$driver = $repository->getDbDriver();
```

**After:**
```php
$executor = $repository->getExecutor();
```

#### Replace `UniqueIdGeneratorInterface` with `MapperFunctionInterface`

**Before:**
```php
use ByJG\MicroOrm\Interface\UniqueIdGeneratorInterface;

class MyGenerator implements UniqueIdGeneratorInterface
{
    // ...
}
```

**After:**
```php
use ByJG\MicroOrm\Interface\MapperFunctionInterface;

class MyGenerator implements MapperFunctionInterface
{
    public function update(mixed $value, object $instance, string $field): mixed
    {
        // your logic
    }

    public function select(mixed $value, object $instance, string $field): mixed
    {
        // your logic
    }
}
```

#### Replace UUID Mapper Classes

**Before:**
```php
use ByJG\MicroOrm\MapperFunctions\SelectBinaryUuidMapper;
use ByJG\MicroOrm\MapperFunctions\UpdateBinaryUuidMapper;

$field->withUpdateFunction(new UpdateBinaryUuidMapper());
$field->withSelectFunction(new SelectBinaryUuidMapper());
```

**After:**
```php
use ByJG\MicroOrm\MapperFunctions\FormatUpdateUuidMapper;
use ByJG\MicroOrm\MapperFunctions\FormatSelectUuidMapper;

$field->withUpdateFunction(new FormatUpdateUuidMapper());
$field->withSelectFunction(new FormatSelectUuidMapper());
```

#### Replace `Mapper::addFieldMap()` with `addFieldMapping()`

**Before:**
```php
$mapper->addFieldMap(
    'propertyName',
    'field_name',
    fn($value) => strtoupper($value),  // update function
    fn($value) => strtolower($value)   // select function
);
```

**After:**
```php
use ByJG\MicroOrm\FieldMapping;

$fieldMapping = FieldMapping::create('propertyName')
    ->withFieldName('field_name')
    ->withUpdateFunction(new class implements MapperFunctionInterface {
        public function update(mixed $value, object $instance, string $field): mixed {
            return strtoupper($value);
        }
        public function select(mixed $value, object $instance, string $field): mixed {
            return strtolower($value);
        }
    });

$mapper->addFieldMapping($fieldMapping);
```

Or use one of the built-in mapper functions:

```php
use ByJG\MicroOrm\FieldMapping;
use ByJG\MicroOrm\MapperFunctions\StandardMapper;

$fieldMapping = FieldMapping::create('propertyName')
    ->withFieldName('field_name')
    ->withUpdateFunction(new StandardMapper())
    ->withSelectFunction(new StandardMapper());

$mapper->addFieldMapping($fieldMapping);
```

#### Replace `MapperClosure` with Mapper Functions

**Before:**
```php
use ByJG\MicroOrm\MapperClosure;

$closure = MapperClosure::forUpdate(fn($value) => json_encode($value));
```

**After:**
```php
use ByJG\MicroOrm\Interface\MapperFunctionInterface;

class JsonEncodeMapper implements MapperFunctionInterface
{
    public function update(mixed $value, object $instance, string $field): mixed
    {
        return json_encode($value);
    }

    public function select(mixed $value, object $instance, string $field): mixed
    {
        return $value;
    }
}

$mapper = new JsonEncodeMapper();
```

### Step 4: Update Type Declarations

Review your code for nullable type declarations. Version 6.0 enforces strict nullable types:

**Before:**
```php
public function myMethod(string $param)  // might accept null in some contexts
```

**After:**
```php
public function myMethod(?string $param)  // explicitly nullable
```

### Step 5: Use `LiteralInterface` Instead of Direct `Literal` Usage

**Before:**
```php
use ByJG\AnyDataset\Db\Literal;

$value = new Literal('NOW()');
```

**After:**
```php
use ByJG\AnyDataset\Db\LiteralInterface;
use ByJG\AnyDataset\Db\Literal;

function processValue(LiteralInterface $value)
{
    // Type hint uses interface
}

$value = new Literal('NOW()');  // Implementation is still Literal
processValue($value);
```

### Step 6: Update Primary Key Seed Functions

If you were using callable for primary key seed functions:

**Before:**
```php
$mapper->withPrimaryKeySeedFunction(function() {
    return generateCustomId();
});
```

**After:**
```php
use ByJG\MicroOrm\Interface\MapperFunctionInterface;

class CustomIdGenerator implements MapperFunctionInterface
{
    public function update(mixed $value, object $instance, string $field): mixed
    {
        return generateCustomId();
    }

    public function select(mixed $value, object $instance, string $field): mixed
    {
        return $value;
    }
}

$mapper->withPrimaryKeySeedFunction(new CustomIdGenerator());
// Or use a string reference to a static class method
$mapper->withPrimaryKeySeedFunction('MyClass::generateId');
```

### Step 7: Handle New Exceptions

Add handling for new exceptions introduced in 6.0:

```php
use ByJG\MicroOrm\Exception\MissingPrimaryKeyException;
use ByJG\MicroOrm\Exception\RequireChangedValuesConstraintException;

try {
    $repository->save($entity);
} catch (MissingPrimaryKeyException $e) {
    // Handle missing primary key
} catch (RequireChangedValuesConstraintException $e) {
    // Handle unchanged values when using RequireChangedValuesConstraint
}
```

### Step 8: Update Tests

- Update PHPUnit to version 10.5+ or 11.5+
- Review test setup to use `ConnectionUtil` for database connections
- Replace all `SqlObject` references with `SqlStatement`
- Update any custom test assertions for type changes

### Step 9: Leverage New Features (Optional)

Take advantage of new features introduced in 6.0:

#### Use Entity Lifecycle Hooks

```php
use ByJG\MicroOrm\Attributes\TableAttribute;

#[TableAttribute(
    tableName: 'users',
    beforeInsert: MyValidator::class . '::validateBeforeInsert',
    beforeUpdate: MyValidator::class . '::validateBeforeUpdate'
)]
class User
{
    // ...
}
```

#### Use ActiveRecordQuery for Fluent API

```php
$products = Product::query()
    ->where('category = :cat', ['cat' => 'electronics'])
    ->orderBy(['price' => 'DESC'])
    ->limit(10)
    ->get();
```

#### Use HAVING Clause

```php
$query = Query::getInstance()
    ->fields(['category', 'COUNT(*) as total'])
    ->groupBy(['category'])
    ->having('COUNT(*) > :min', ['min' => 5]);
```

#### Add Update Constraints

```php
use ByJG\MicroOrm\Constraint\RequireChangedValuesConstraint;

$repository->setUpdateConstraint(new RequireChangedValuesConstraint());
```

### Step 10: Run Tests and Verify

After making all changes:

```bash
# Update dependencies
composer update

# Run static analysis
vendor/bin/psalm

# Run tests
vendor/bin/phpunit
```

## Additional Notes

### Performance Improvements

- Serialization is now optimized with `withStopAtFirstLevel()`
- Iterator support allows efficient handling of large datasets
- Better query caching through refined cache key generation

### Code Quality

- Full PHP 8.3+ type safety
- Added `#[Override]` attributes for better IDE support
- Comprehensive Psalm static analysis compliance
- Improved code documentation and examples

### Testing

- Expanded test coverage for all new features
- Better edge case handling in tests
- Centralized database connection logic in tests
- Support for Docker-based testing with MySQL

## Migration Checklist

- [ ] Update PHP to 8.3 or higher
- [ ] Update `composer.json` to require `byjg/micro-orm: ^6.0`
- [ ] Run `composer update`
- [ ] Replace all `SqlObject` with `SqlStatement`
- [ ] Replace `DbFunctionsInterface` with `DatabaseExecutor`
- [ ] Replace `UniqueIdGeneratorInterface` with `MapperFunctionInterface`
- [ ] Replace UUID mapper classes (`SelectBinaryUuidMapper` → `FormatSelectUuidMapper`, etc.)
- [ ] Replace `Mapper::addFieldMap()` with `Mapper::addFieldMapping()`
- [ ] Remove usage of deprecated `MapperClosure` class
- [ ] Update type hints to use `LiteralInterface` where applicable
- [ ] Update primary key seed functions to new signature
- [ ] Add exception handling for `MissingPrimaryKeyException` and `RequireChangedValuesConstraintException`
- [ ] Update PHPUnit to 10.5+ or 11.5+
- [ ] Run Psalm and fix any type issues
- [ ] Run test suite and verify all tests pass
- [ ] Review and update documentation/comments

## Support

For issues, questions, or contributions:
- GitHub Issues: https://github.com/byjg/php-micro-orm/issues
- Documentation: See the `docs/` folder for detailed guides

## Credits

This release includes contributions focused on improving type safety, developer experience, and ORM functionality. Special attention was given to maintaining clean architecture patterns and comprehensive documentation.
