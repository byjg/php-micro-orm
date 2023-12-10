<?php

namespace ByJG\MicroOrm;

use ByJG\MicroOrm\Exception\AllowOnlyNewValuesConstraintException;
use ByJG\Serializer\SerializerObject;

class UpdateConstraint
{
    private array $allowOnlyNewValuesForFields = [];

    public static function instance(): self
    {
        return new self();
    }

    public function withAllowOnlyNewValuesForFields(array|string $properties): self
    {
        $this->allowOnlyNewValuesForFields = array_merge($this->allowOnlyNewValuesForFields, (array)$properties);
        return $this;
    }

    public function check($oldInstance, $newInstance)
    {
        foreach ($this->allowOnlyNewValuesForFields as $property) {
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
    }
}