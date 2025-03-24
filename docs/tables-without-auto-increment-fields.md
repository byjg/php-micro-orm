---
sidebar_position: 15
---

# Tables without auto increments fields

Some tables don't have auto-increment fields, for example, when you have a table with UUID binary. 
In this case, you need to provide a function to calculate the unique ID.

```php
<?php
// Creating the mapping
$mapper = new \ByJG\MicroOrm\Mapper(
    Users::class,   // The full qualified name of the class
    'users',        // The table that represents this entity
    'id',            // The primary key field
    function () {
        // calculate and return the unique ID
    }
);
```

