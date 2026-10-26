<?php

namespace Tests\Model;

use ByJG\MicroOrm\Interface\EntityValidatorInterface;
use InvalidArgumentException;

/**
 * Records every call, and rejects an entity whose name is empty.
 */
class RecordingValidator implements EntityValidatorInterface
{
    /** @var array<int, array{instance: mixed, isInsert: bool, id: mixed}> */
    public static array $calls = [];

    #[\Override]
    public function validate(mixed $instance, bool $isInsert): void
    {
        self::$calls[] = ['instance' => $instance, 'isInsert' => $isInsert, 'id' => $instance->getId()];

        if ($instance->name === '') {
            throw new InvalidArgumentException('The name is empty');
        }
    }
}
