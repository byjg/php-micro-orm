<?php

namespace ByJG\MicroOrm\Constraint;

use ByJG\MicroOrm\Exception\UpdateConstraintException;
use ByJG\MicroOrm\Interface\UpdateConstraintInterface;
use Closure;

class CustomConstraint implements UpdateConstraintInterface
{
    private Closure $closure;
    private ?string $errorMessage;

    /**
     * @param Closure $closure A closure that receives $oldInstance and $newInstance and returns true if valid
     * @param string|null $errorMessage Custom error message when constraint fails
     */
    public function __construct(Closure $closure, ?string $errorMessage = null)
    {
        $this->closure = $closure;
        $this->errorMessage = $errorMessage;
    }

    /**
     * @inheritDoc
     */
    public function check(mixed $oldInstance, mixed $newInstance): void
    {
        $result = ($this->closure)($oldInstance, $newInstance);
        if ($result !== true) {
            throw new UpdateConstraintException(
                $this->errorMessage ?? "The Update Constraint validation failed"
            );
        }
    }
} 