# Auto Discovering Relationship

The `FieldAttribute` has a parameter `parentTable` that is used to define the parent table of the field. This is used in
the case of a foreign key.

Once this attribute is set, the `Repository` can auto-discover the relationship between the tables
and generate the proper SQL with the relationship to retrieve the data.

## How to use

You can use the `parentTable` parameter in the `FieldAttribute` to define the parent table of the field.

```php
<?php
#[TableAttribute('table1')]
class Class1
{
    #[FieldAttribute(primaryKey: true)]
    public ?int $id;
}

#[TableAttribute('table2')]
class Class2
{
    #[FieldAttribute(primaryKey: true)]
    public ?int $id;

    #[FieldAttribute(fieldName: "id_table1", parentTable: "table1")]
    public ?int $idTable1;
}

$repository1 = new Repository($dbDriver, Class1::class);
$repository2 = new Repository($dbDriver, Class2::class);
```

This will automatically create the relationship between `table1` and `table2`.

To generate the SQL with the relationship you can use the `ORM` static class:

```php
<?php
$query = ORM::getQueryInstance("table1", "table2");
```

The command above will return a query object similar to:

```php
<?php
$query = Query::getInstance()
    ->table('table1')
    ->join('table2', 'table2.id_table1 = table1.id');
```

## Limitations

- This feature does not support multiple relationships between the same tables
- Primary keys with two or more fields are not supported.

