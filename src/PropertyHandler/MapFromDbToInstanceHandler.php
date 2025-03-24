<?php

namespace ByJG\MicroOrm\PropertyHandler;

use ByJG\MicroOrm\FieldMapping;
use ByJG\MicroOrm\Mapper;
use ByJG\Serializer\PropertyHandler\PropertyHandlerInterface;
use Override;

class MapFromDbToInstanceHandler implements PropertyHandlerInterface
{

    protected ?FieldMapping $fieldMap = null;
    public function __construct(private Mapper $mapper)
    {
    }

    #[Override]
    /**
     * Note:
     * This implementation is dependent on how ObjectCopy works.
     * First it will call mapName and then transformValue
     * If the order changes, this implementation must be changed.
     *
     * @param string $property In this context is the field name
     */
    public function mapName(string $property): string
    {
        $propertyName = $this->mapper->getPropertyName($property);
        $this->fieldMap = $this->mapper->getFieldMap($propertyName);
        return $propertyName;
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
        return $this->fieldMap?->getSelectFunctionValue($value, $instance) ?? $value;
    }
}