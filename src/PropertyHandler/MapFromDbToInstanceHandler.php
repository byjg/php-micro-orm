<?php

namespace ByJG\MicroOrm\PropertyHandler;

use ByJG\MicroOrm\Mapper;
use ByJG\Serializer\PropertyHandler\PropertyHandlerInterface;
use Override;

class MapFromDbToInstanceHandler implements PropertyHandlerInterface
{

    public function __construct(private Mapper $mapper)
    {
    }

    #[Override]
    /**
     * @param string $property In this context is the field name
     */
    public function mapName(string $property): string
    {
        return $this->mapper->getPropertyName($property);
    }

    /**
     *
     * @param string $propertyName In this context is the field name
     * @param string $targetName In this context is the property name
     * @param mixed $value The value to be transformed
     * @param mixed|null $instance This is an array with the values from the database
     * @return mixed
     */
    #[Override]
    public function transformValue(string $propertyName, string $targetName, mixed $value, mixed $instance = null): mixed
    {
        $fieldMap = $this->mapper->getFieldMap($this->mapper->getPropertyName($propertyName));
        if (empty($fieldMap)) {
            $fieldMap = $this->mapper->getFieldMap($targetName);
        }

        return $fieldMap?->getSelectFunctionValue($value, $instance) ?? $value;
    }
}