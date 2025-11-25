---
sidebar_position: 15
---

# Tables without auto increments fields

Some tables don't have auto-increment fields, for example, when you have a table with UUID binary or varchar primary
keys.
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

## Varchar Primary Keys

For tables with varchar primary keys where you want to provide the value yourself (e.g., SKU codes, custom IDs),
you need to create a seed function that returns the value from the instance. This prevents the ORM from trying
to use auto-increment behavior.

### Using Repository Pattern

```php
<?php
use ByJG\MicroOrm\Mapper;
use ByJG\MicroOrm\Repository;
use ByJG\MicroOrm\Interface\MapperFunctionInterface;

// Create a simple mapper function that returns the primary key value from the instance
class SkuSeedFunction implements MapperFunctionInterface
{
    public function processedValue(mixed $value, mixed $instance, ?\ByJG\AnyDataset\Db\DatabaseExecutor $executor = null): mixed
    {
        // Return the SKU value that's already set in the instance
        return $instance->getSku();
    }
}

// Create the mapper with the seed function
$mapper = new Mapper(
    Product::class,
    'products',
    'sku'
);
$mapper->withPrimaryKeySeedFunction(new SkuSeedFunction());

$repository = new Repository($executor, $mapper);

// Now you can save products with varchar primary keys
$product = new Product();
$product->setSku('PROD-001');
$product->setName('Laptop');
$product->setPrice(999.99);
$repository->save($product);
```

### Using Active Record Pattern

For Active Record models, you can create a custom TableAttribute that implements MapperFunctionInterface:

```php
<?php
use ByJG\MicroOrm\Attributes\TableAttribute;
use ByJG\MicroOrm\Interface\MapperFunctionInterface;

#[Attribute(Attribute::TARGET_CLASS)]
class ProductTableAttribute extends TableAttribute implements MapperFunctionInterface
{
    public function __construct()
    {
        parent::__construct('products', ProductTableAttribute::class);
    }

    public function processedValue(mixed $value, mixed $instance, ?\ByJG\AnyDataset\Db\DatabaseExecutor $executor = null): mixed
    {
        // Return the SKU value from the instance
        if ($instance instanceof Product) {
            return $instance->getSku();
        }
        return $value;
    }
}

#[ProductTableAttribute]
class Product
{
    use ActiveRecord;

    #[FieldAttribute(primaryKey: true)]
    protected $sku;

    #[FieldAttribute]
    protected $name;

    #[FieldAttribute]
    protected $price;

    // getters and setters...
}

// Usage
Product::initialize($executor);

$product = Product::new([
    'sku' => 'PROD-001',
    'name' => 'Laptop',
    'price' => 999.99
]);
$product->save();

$retrieved = Product::get('PROD-001');
```

**Important Note:** When using varchar primary keys, the seed function is required to prevent the ORM from
attempting to retrieve an auto-increment ID from the database. Without this function, the ORM will call
`executeAndGetInsertedId()` which may return unexpected values for non-integer primary keys.

