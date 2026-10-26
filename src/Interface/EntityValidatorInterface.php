<?php

namespace ByJG\MicroOrm\Interface;

/**
 * Interface for validators that check an entity before it is saved.
 */
interface EntityValidatorInterface
{
    /**
     * Validate an entity before it is saved. It receives the instance itself, before any
     * field is mapped to its column or converted for the database, and rejects it by
     * throwing an exception, which stops the save.
     *
     * @param mixed $instance The entity being saved
     * @param bool $isInsert Whether the save inserts a new row or updates an existing one
     * @return void
     */
    public function validate(mixed $instance, bool $isInsert): void;
}
