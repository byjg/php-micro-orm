<?php

namespace ByJG\MicroOrm\Interface;

use ByJG\MicroOrm\Exception\UpdateConstraintException;

interface UpdateConstraintInterface
{
    /**
     * Check if the constraint is valid
     *
     * @param mixed $oldInstance The old instance before update
     * @param mixed $newInstance The new instance to be saved
     * @throws UpdateConstraintException
     */
    public function check(mixed $oldInstance, mixed $newInstance): void;
} 