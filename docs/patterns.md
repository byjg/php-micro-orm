---
sidebar_position: 2
---

# Choosing a Pattern

MicroORM implements three of Martin Fowler's enterprise patterns:
[Data Mapper](https://martinfowler.com/eaaCatalog/dataMapper.html),
[Repository](https://martinfowler.com/eaaCatalog/repository.html) and
[Active Record](https://martinfowler.com/eaaCatalog/activeRecord.html).

They are not three alternatives. They are layers, and every way of using MicroORM goes
through all of them:

```text
   Repository + Mapper object   Repository + attributes      ActiveRecord trait
   (pure Data Mapper)           (pragmatic Data Mapper)      (facade on the model)
            │                            │                            │
            │                            │                 creates internally
            │                            │                            │
            └────────────────────────────┼────────────────────────────┘
                                         ▼
                                    Repository
                                         │
                                         ▼
                      Mapper  ◄── built from a Mapper object or from the
                         │        #[TableAttribute] / #[FieldAttribute] attributes
                         ▼
                 DatabaseExecutor ──► DB
```

- The **Mapper** is the Data Mapper. It knows how a model's properties translate to a
  table's columns. It is always there.
- The **Repository** does the reading and writing, using the Mapper. It is always there
  too: the `ActiveRecord` trait creates one for the model the first time it is used.

So the choice is not *which* pattern, but two separate questions:

1. **Where does the mapping live?** In a separate `Mapper` object, or as attributes on
   the model.
2. **Where do the persistence calls live?** In a repository (`$repository->save($model)`),
   or on the model itself (`$model->save()`).

## The three combinations

### Repository + Mapper object (pure Data Mapper)

The model has no knowledge of the database. The mapping is defined outside it:

```php
class User
{
    public ?int $id = null;
    public ?string $name = null;
}

$mapper = new Mapper(User::class, 'users', 'id');
$repository = new Repository($executor, $mapper);

$user = $repository->get(1);
$repository->save($user);
```

Use it when the model must stay free of database concerns: Domain-Driven Design, legacy
or third-party classes you cannot change, or one class mapped to more than one table.
See [Using the Mapper Object](using-mapper-object.md).

### Repository + attributes (pragmatic Data Mapper)

The mapping is declared on the model with attributes, and the repository still does the
persistence:

```php
#[TableAttribute(tableName: 'users')]
class User
{
    #[FieldAttribute(primaryKey: true)]
    public ?int $id = null;

    #[FieldAttribute]
    public ?string $name = null;
}

$repository = new Repository($executor, User::class);   // builds the Mapper from the attributes

$user = $repository->get(1);
$repository->save($user);
```

The model now carries mapping metadata, but it still has no persistence methods, and
your code depends on the repository, which is easy to replace in a test. This is the
most common choice. See [Model Attributes](model-attribute.md) and
[The Repository Class](repository.md).

### ActiveRecord trait

The model gets the persistence methods itself:

```php
#[TableAttribute(tableName: 'users')]
class User
{
    use ActiveRecord;

    #[FieldAttribute(primaryKey: true)]
    public ?int $id = null;

    #[FieldAttribute]
    public ?string $name = null;
}

User::initialize($executor);

$user = User::get(1);
$user->save();
```

There is no repository class to write. Behind the trait is the same `Repository` and the
same `Mapper`, so everything else in this documentation (literals, soft delete, observers,
mapper functions) works the same way. By default the Mapper comes from the attributes;
override `discoverClass()` to supply a `Mapper` object instead. See
[Active Record](active-record.md).

## Which one to choose

|                                     | Repository + Mapper object | Repository + attributes | ActiveRecord trait            |
|-------------------------------------|----------------------------|-------------------------|-------------------------------|
| **Model knows the database schema** | No                         | Yes (attributes)        | Yes (attributes)              |
| **Model knows how to persist**      | No                         | No                      | Yes                           |
| **Classes to write**                | Model + mapper setup       | Model                   | Model                         |
| **Replace persistence in tests**    | Easy (inject a repository) | Easy (inject a repository) | Harder (static methods)    |
| **Map a class you cannot change**   | Yes                        | No                      | No                            |
| **Best for**                        | DDD, legacy and third-party models | Most applications | Simple CRUD, prototypes |

You do not have to pick one for the whole application. All three use the same
`Repository` and `Mapper`, so they can live side by side: an `ActiveRecord` model for a
simple lookup table, a repository for the entity with the business rules.

## Repository or Query?

A separate question is whether to go through the `Repository` at all, or build a `Query`
and read raw rows. That is about entities versus arrays, not about the patterns on this
page. See [Architecture Layers: Infrastructure vs Domain](architecture-layers.md).
