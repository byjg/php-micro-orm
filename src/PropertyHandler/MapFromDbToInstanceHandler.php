<?php

namespace ByJG\MicroOrm\PropertyHandler;

use BackedEnum;
use ByJG\AnyDataset\Db\Interfaces\EntityHydratorInterface;
use ByJG\MicroOrm\FieldMapping;
use ByJG\MicroOrm\Mapper;
use ByJG\Serializer\ObjectCopy;
use ByJG\Serializer\PropertyHandler\PropertyHandlerInterface;
use Override;
use stdClass;

/**
 * Maps the columns of a row to the properties of the entity of a Mapper (property names, selectFunction).
 *
 * As a property handler, ObjectCopy calls it for each value. As an entity hydrator, it fills each entity itself
 * with the same result: how each column is written is resolved once per query, and each row is one loop.
 */
class MapFromDbToInstanceHandler implements PropertyHandlerInterface, EntityHydratorInterface
{

    protected ?FieldMapping $fieldMap = null;

    /**
     * The property name and the field mapping of each field name, resolved once (the handler lives for a query)
     *
     * @var array<string, array{0: string, 1: FieldMapping|null}>
     */
    private array $fields = [];

    /**
     * How each column is written, resolved once (the handler lives for a query): the property name, the field
     * mapping, and ObjectCopy::writerFor() of the entity class
     *
     * @var array<string, array{0: string, 1: FieldMapping|null, 2: array{0: string, 1: bool, 2: class-string<BackedEnum>|null}|false}>
     */
    private array $plan = [];

    public function __construct(private Mapper $mapper)
    {
    }

    /**
     * The same values as ObjectCopy::copy(array_merge($defaults, $row), $entity, $this): for each key in that
     * order, the property name (mapName()), the selectFunction (transformValue()), then the setter or the public
     * property of the entity, as ObjectCopy writes it (a backed enum member takes the enum).
     */
    #[Override]
    public function hydrate(array $row, object $entity, array $defaults): void
    {
        $source = array_merge($defaults, $row);
        $className = get_class($entity);
        $isStdClass = $entity instanceof stdClass;

        foreach ($source as $key => $value) {
            $key = (string)$key;
            if (!isset($this->plan[$key])) {
                $propertyName = $this->mapName($key);
                $this->plan[$key] = [
                    $propertyName,
                    $this->fieldMap,
                    $isStdClass ? false : ObjectCopy::writerFor($className, $propertyName),
                ];
            }
            [$propertyName, $fieldMap, $writer] = $this->plan[$key];

            if ($value instanceof BackedEnum) {
                $value = $value->value;
            }
            if ($fieldMap !== null) {
                $value = $fieldMap->getSelectFunctionValue($value, $source) ?? $value;
            }

            if ($writer === false) {
                // stdClass takes any name; another class, a property the instance has (dynamic or magic)
                if ($isStdClass || isset($entity->{$propertyName})) {
                    $entity->{$propertyName} = $value;
                }
                continue;
            }

            [$member, $isSetter, $enum] = $writer;
            if ($enum !== null && (is_int($value) || is_string($value))) {
                $value = $enum::from($value);
            }
            if ($isSetter) {
                $entity->{$member}($value);
                continue;
            }
            $entity->{$member} = $value;
        }
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