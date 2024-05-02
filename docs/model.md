# The Model Attributes

The Model Attributes are used to define the table structure in the database. 

The attributes are:

* `TableAttribute`: Used in the class level. Define that the Model is referencing a table in the database.
* `FieldAttribute`: Define the properties in the class that are fields in the database.

## Example

```php
#[TableAttribute(tableName: 'mytable')]
class MyModel
{
    #[FieldAttribute(primaryKey: true)]
    public ?int $id;

    #[FieldAttribute()]
    public ?string $name;

    #[FieldAttribute(fieldName: 'company_id')
    public ?int $companyId;
    
    #[FieldAttribute(fieldName: 'created_at')]
    protected ?string $createdAt;
    
    #[FieldAttribute(fieldName: 'updated_at')]
    protected ?string $updatedAt;
    
    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }
    
    public function setCreatedAt(?string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
    
    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }
    
    public function setUpdatedAt(?string $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
```

In this example, we have a class `MyModel` with five properties: `id`, `name`, `companyId`, `createdAt`, and `updatedAt`.

The `id` property is marked as a primary key. The `name` property is a simple field. 
The `companyId` property is a field with a different name in the database `company_id`.
The same for `createdAt` and `updatedAt`. These properties are fields with a different name in the database `created_at` and `updated_at`.

The `TableAttribute` is used to define the table name in the database.

## Where to use FieldAttribute

The `FieldAttribute` can be used in the following properties:

* Public properties
* Protected properties
* Private properties

**Do not use the `FieldAttribute` in any _method_, _getter_ or _setter_.**

## Rules for the properties

* If the property has a type, then must be nullable. If the property is not nullable, you must set a default value.
* You can use mixed or not typed properties.
* If the property is protected or private, you must have a getter and setter for this property.

## Table Attributes parameters

The `TableAttribute` has the following parameters:

* `tableName`: The name of the table in the database.
* `primaryKeySeedFunction` (optional): A function that returns the seed for the primary key. The function must return a value.

## Field Attributes parameters

The `FieldAttribute` has the following parameters:

* primaryKey (optional): If the field is a primary key.
* fieldName (optional): The name of the field in the database. If not set, the field name is the same as the property name.
* fieldAlias (optional): The alias of the field in the database. If not set, the field alias is the same as the property name.
* syncWithDb (optional): If the field should be synchronized with the database. Default is true.
* updateFunction (optional): A function that is called when the field is updated. The function must return a value.
* selectFunction (optional): A function that is called when the field is selected. The function must return a value.

```tip
To use a function as a parameter, you must inherit from the `FieldAttribute` and
in the constructor call the parent with the function.
```

## Special Field Attributes: FieldReadOnlyAttribute

* It is used to define a field that is read-only.
* It sets the MapperClosure::readonly() method to the updateFunction.

```php

## Closure Function Signature

### primaryKeySeedFunction:

```php
function (object $instance) {
    // $instance is the instance of the model with the properties set
    return mixed;
}
```

### selectFunction and updateFunction:

```php
function (mixed $fieldValue, mixed $data) {
    // $value is the value to be set in the property
    // $data is an array with the value properties set
    return $fieldValue;
}
```


