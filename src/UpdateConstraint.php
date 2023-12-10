<?php

namespace ByJG\MicroOrm;

use ByJG\MicroOrm\Exception\AllowOnlyNewValuesConstraintException;
use ByJG\MicroOrm\Exception\UpdateConstraintException;
use ByJG\Serializer\SerializerObject;

class UpdateConstraint
{
    private array $closureValidation = [];

    public static function instance(): self
    {
        return new self();
    }

    public function withAllowOnlyNewValuesForFields(array|string $properties): self
    {
        $this->withClosureValidation(function($oldInstance, $newInstance) use ($properties) {
            foreach ((array)$properties as $property) {
                $method = "get" . ucfirst($property);
                if (!method_exists($oldInstance, $method)) {
                    throw new AllowOnlyNewValuesConstraintException("The property '$property' does not exists in the old instance.");
                }
                if (!method_exists($newInstance, $method)) {
                    throw new AllowOnlyNewValuesConstraintException("The property '$property' does not exists in the new instance.");
                }
                if ($oldInstance->$method() == $newInstance->$method()) {
                    throw new AllowOnlyNewValuesConstraintException("You are not updating the property '$property' ");
                }
            }
            return true;
        });
        return $this;
    }

    public function withClosureValidation(\Closure $closure): self
    {
        $this->closureValidation[] = $closure;
        return $this;
    }

    public function check($oldInstance, $newInstance)
    {
        foreach ($this->closureValidation as $closure) {
            if ($closure($oldInstance, $newInstance) !== true) {
                throw new UpdateConstraintException("The Update Constraint validation failed");
            };
        }
    }
}