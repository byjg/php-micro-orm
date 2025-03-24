<?php

namespace ByJG\MicroOrm\Constraint;

use ByJG\MicroOrm\Exception\RequireChangedValuesConstraintException;
use ByJG\MicroOrm\Interface\UpdateConstraintInterface;

class RequireChangedValuesConstraint implements UpdateConstraintInterface
{
    private array $properties;

    /**
     * @param array|string $properties The property(ies) that must be different from the old instance
     */
    public function __construct(array|string $properties)
    {
        $this->properties = (array)$properties;
    }

    /**
     * @inheritDoc
     */
    public function check(mixed $oldInstance, mixed $newInstance): void
    {
        foreach ($this->properties as $property) {
            $method = "get" . ucfirst($property);
            if (!method_exists($oldInstance, $method)) {
                throw new RequireChangedValuesConstraintException("The property '$property' does not exist in the old instance.");
            }
            if (!method_exists($newInstance, $method)) {
                throw new RequireChangedValuesConstraintException("The property '$property' does not exist in the new instance.");
            }
            if ($oldInstance->$method() == $newInstance->$method()) {
                throw new RequireChangedValuesConstraintException("You are not updating the property '$property'");
            }
        }
    }
} 