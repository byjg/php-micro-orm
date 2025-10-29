---
sidebar_position: 11
---

# Common Traits for Timestamp Fields

MicroOrm provides several utility traits that help you handle common timestamp fields in your database tables.
These traits make it easy to implement standard fields like `created_at`, `updated_at`, and `deleted_at` in your models.

## Available Traits

### CreatedAt Trait

The `CreatedAt` trait automatically handles the `created_at` timestamp field, setting it to the current UTC time when a
record is created,
and preventing it from being updated on subsequent saves.

```php
use ByJG\MicroOrm\Trait\CreatedAt;

class MyModel
{
    use CreatedAt;
    
    // The rest of your model properties and methods
}
```

When you use this trait, it adds a `created_at` field to your model with the following features:

- Sets the timestamp automatically when a record is first created using the `NowUtcMapper`
- Prevents the field from being updated on subsequent saves using the `ReadOnlyMapper`
- Provides getter and setter methods for the field

### UpdatedAt Trait

The `UpdatedAt` trait automatically handles the `updated_at` timestamp field, setting it to the current UTC time
whenever a record is updated.

```php
use ByJG\MicroOrm\Trait\UpdatedAt;

class MyModel
{
    use UpdatedAt;
    
    // The rest of your model properties and methods
}
```

When you use this trait, it adds an `updated_at` field to your model with the following features:

- Updates the timestamp automatically whenever a record is saved using the `NowUtcMapper`
- Provides getter and setter methods for the field

### DeletedAt Trait

The `DeletedAt` trait adds support for the soft delete pattern, where records are marked as deleted instead of being
physically removed from the database.

```php
use ByJG\MicroOrm\Trait\DeletedAt;

class MyModel
{
    use DeletedAt;
    
    // The rest of your model properties and methods
}
```

When you use this trait, it adds a `deleted_at` field to your model with the following features:

- Field is marked with `syncWithDb: false` which ensures it is included in database operations
- Enables soft delete functionality in the Repository class
- Provides getter and setter methods for the field

For more details on soft delete functionality, see the [Soft Delete](softdelete.md) documentation.

## Using Multiple Traits Together

You can use multiple traits together to implement a complete timestamp management solution:

```php
use ByJG\MicroOrm\Attributes\FieldAttribute;
use ByJG\MicroOrm\Attributes\TableAttribute;
use ByJG\MicroOrm\Trait\CreatedAt;
use ByJG\MicroOrm\Trait\UpdatedAt;
use ByJG\MicroOrm\Trait\DeletedAt;

#[TableAttribute(tableName: 'mytable')]
class MyModel
{
    use CreatedAt;
    use UpdatedAt;
    use DeletedAt;
    
    #[FieldAttribute(primaryKey: true)]
    public ?int $id;
    
    #[FieldAttribute()]
    public ?string $name;
    
    // The rest of your model properties and methods
}
```

Using these traits provides a standardized way to handle timestamps across your models and ensures consistent behavior. 