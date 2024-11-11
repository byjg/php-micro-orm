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

| Field                      | Description                                                                             | Required |
|----------------------------|-----------------------------------------------------------------------------------------|:--------:|
| **tableName**              | The name of the table in the database.                                                  |   Yes    |
| **primaryKeySeedFunction** | A function that returns the seed for the primary key. The function must return a value. |    No    |

## Field Attributes parameters

The `FieldAttribute` has the following parameters:

| Field              | Description                                                                                                                                      | Required |
|--------------------|--------------------------------------------------------------------------------------------------------------------------------------------------|:--------:|
| **primaryKey**     | If the field is a primary key. It is required at least one field to be a PK.  It is used for insert/updates/deletes.                             |    No    |
| **fieldName**      | The name of the field in the database. If not set, the field name is the same as the property name.                                              |    No    |
| **fieldAlias**     | The alias of the field in the database. If not set, the field alias is the same as the field name.                                               |    No    |
| **syncWithDb**     | If the field should be synchronized with the database. Default is true.                                                                          |    No    |
| **updateFunction** | A function that is called when the field is updated. The function must return a value.                                                           |    No    |
| **selectFunction** | A function that is called when the field is selected. The function must return a value.                                                          |    No    |
| **insertFunction** | A function that is called when the field is inserted. The function must return a value.                                                          |    No    |
| **parentTable**    | The parent table of the field. This is used in the case of a foreign key. See [Auto Discovering Relationship](auto-discovering-relationship.md). |    No    |

See also [Controlling the Data](controlling-the-data.md) for more information about the `updateFunction`,
`selectFunction`, and `insertFunction`.


