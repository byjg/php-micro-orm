<?php

namespace ByJG\MicroOrm\PropertyHandler;

use ByJG\MicroOrm\FieldMapping;
use ByJG\MicroOrm\Mapper;
use ByJG\Serializer\PropertyHandler\PropertyHandlerInterface;
use Override;

class MapFromDbToInstanceHandler implements PropertyHandlerInterface
{

    protected ?FieldMapping $fieldMap = null;

    /**
     * The property name and the field mapping of each field name, resolved once (the handler lives for a query)
     *
     * @var array<string, array{0: string, 1: FieldMapping|null}>
     */
    private array $fields = [];

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
        if (!isset($this->fields[$property])) {
            $propertyName = $this->mapper->getPropertyName($property);
            $fieldMap = $this->mapper->getFieldMap($propertyName);
            $this->fields[$property] = [$propertyName, $fieldMap instanceof FieldMapping ? $fieldMap : null];
        }
        [$propertyName, $this->fieldMap] = $this->fields[$property];
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