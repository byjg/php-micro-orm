# Update Constraints

An update constraint is a way to define rules to update a record.
If the constraint is not satisfied the update will not be performed.

```php
<?php
$updateConstraint = \ByJG\MicroOrm\UpdateConstraint()::instance()
    ->withAllowOnlyNewValuesForFields('name');

$users->name = "New name";
$repository->save($users, $updateConstraint);
```

## Current Constraints

### Allow Only New Values for Fields

This constraint will allow only new values for the fields defined.

### Custom Constraint

```php
$updateConstraint = \ByJG\MicroOrm\UpdateConstraint()::instance()
    ->withClosure(function($oldInstance, $newInstance) {
        return true; // to allow the update, or false to block it.
    });
```
