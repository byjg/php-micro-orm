<?php

namespace ByJG\MicroOrm\PropertyHandler;

use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\MicroOrm\Mapper;
use ByJG\Serializer\PropertyHandler\PropertyHandlerInterface;
use Override;

class PrepareToUpdateHandler implements PropertyHandlerInterface
{
    private Mapper $mapper;
    private array $fieldToProperty = [];
    private mixed $instance;
    private DatabaseExecutor $executor;

    public function __construct(Mapper $mapper, mixed $instance, DatabaseExecutor $executor)
    {
        $this->mapper = $mapper;
        $this->instance = $instance;
        $this->executor = $executor;
    }

    #[Override]
    public function mapName(string $property): string
    {
        $sourcePropertyName = $this->mapper->fixFieldName($property);
        return $this->mapper->getFieldMap($sourcePropertyName)?->getFieldName() ?? $sourcePropertyName ?? $property;
    }

    #[Override]
    public function transformValue(string $propertyName, string $targetName, mixed $value, mixed $instance = null): mixed
    {
        $fieldMap = $this->mapper->getFieldMap($propertyName);
        return $fieldMap?->getUpdateFunctionValue($value, $this->instance, $this->executor) ?? $value;
    }
}
