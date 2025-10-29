<?php

namespace ByJG\MicroOrm\Interface;

/**
 * Interface for processors that manipulate entity instances before database operations.
 */
interface EntityProcessorInterface
{
    /**
     * Process an entity instance before it's inserted or updated in the database.
     *
     * @param array $instance The entity instance to process
     * @return array The processed entity instance
     */
    public function process(array $instance): array;
} 