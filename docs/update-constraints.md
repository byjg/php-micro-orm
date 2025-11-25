---
sidebar_position: 17
---

# Update Constraints

An update constraint is a way to define rules to validate an update to a record.
If the constraint is not satisfied, the update will not be performed and an exception will be thrown.

## Available Constraints

### RequireChangedValuesConstraint

This constraint will require that the specified properties must have different values between the old and new instances.
It's useful when you want to ensure that certain fields are actually being updated.

```php
<?php
// This will ensure the 'name' field is actually being changed
$repository->save($user, new RequireChangedValuesConstraint('name'));

// You can also specify multiple fields that must change
$repository->save($user, new RequireChangedValuesConstraint(['name', 'email']));
```

If you try to update a record without changing the specified field(s), a `RequireChangedValuesConstraintException` will
be thrown.

### CustomConstraint

This constraint allows you to define custom validation logic using a closure. The closure receives the old and new
instances and should return `true` if the update should be allowed, or any other value to reject it.

```php
<?php
$repository->save($user, new CustomConstraint(
    function($oldInstance, $newInstance) {
        // Only allow updating if the new age is greater than the old age
        return $newInstance->getAge() > $oldInstance->getAge();
    },
    "Age can only be increased" // Optional custom error message
));
```

If the validation fails, an `UpdateConstraintException` will be thrown with either your custom message or a default
message.

## Using Multiple Constraints

You can apply multiple constraints by passing an array of constraints to the `save` method:

```php
<?php
$repository->save($user, [
    new RequireChangedValuesConstraint(['name', 'email']),
    new CustomConstraint(function($old, $new) {
        return $new->getAge() >= 18;
    }, "User must be at least 18 years old")
]);
```

All constraints must pass for the update to be performed.

## Creating Custom Constraints

You can create your own constraints by implementing the `UpdateConstraintInterface`:

```php
<?php
use ByJG\MicroOrm\Interface\UpdateConstraintInterface;
use ByJG\MicroOrm\Exception\UpdateConstraintException;

class MyCustomConstraint implements UpdateConstraintInterface
{
    public function check(mixed $oldInstance, mixed $newInstance): void
    {
        // Your custom validation logic
        if (!$this->isValid($oldInstance, $newInstance)) {
            throw new UpdateConstraintException("Your custom error message");
        }
    }
    
    private function isValid($old, $new): bool
    {
        // Implement your validation logic here
        return true;
    }
}
```
