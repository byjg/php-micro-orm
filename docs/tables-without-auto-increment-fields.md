---
sidebar_position: 15
---

# Tables without auto increments fields

Some tables don't have auto-increment fields, for example, when you have a table with UUID binary or varchar primary
keys.
In this case, you can provide a seed function (a `MapperFunctionInterface`) to calculate the unique ID.

```php
<?php
// Creating the mapping
$mapper = new \ByJG\MicroOrm\Mapper(
    Users::class,   // The full qualified name of the class
    'users',        // The table that represents this entity
    'id'            // The primary key field
);
$mapper->withPrimaryKeySeedFunction(MyIdSeedFunction::class); // calculates and returns the unique ID
```

A primary key set on the instance before the first `save()` is inserted as given; the seed function is only called
when the key is empty.

## Varchar Primary Keys

For tables with varchar primary keys where you want to provide the value yourself (e.g., SKU codes, custom IDs),
setting the key on the instance is enough: it is inserted as given. A seed function that returns the value from the
instance, as below, is optional.

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

**Important Note:** Only when the primary key is empty and there is no seed function does the ORM read the
auto-increment ID back from the database (`executeAndGetInsertedId()`).

