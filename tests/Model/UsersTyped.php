<?php

namespace Tests\Model;

/**
 * Model whose typed properties are non-nullable and have no default value, so a freshly
 * constructed instance keeps them "uninitialized". The repository hydration path serializes an
 * empty instance (via anydataset-db's PreFetchTrait) to enumerate the fields before copying the
 * row into it, which used to raise "Typed property ... must not be accessed before initialization".
 *
 * The property names match the `users` table columns, so no field mapping is required.
 */
class UsersTyped
{
    public int $id;

    public string $name;

    public string $createdate;
}