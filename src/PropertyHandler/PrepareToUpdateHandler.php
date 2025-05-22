<?php

namespace ByJG\MicroOrm\PropertyHandler;

use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\MicroOrm\Mapper;
use ByJG\Serializer\PropertyHandler\PropertyHandlerInterface;
use Override;

class PrepareToUpdateHandler implements PropertyHandlerInterface
{
    private Mapper $mapper;
    private array $fieldToProperty = [];
    private mixed $instance;
    private DbDriverInterface $dbDriverWrite;

    public function __construct(Mapper $mapper, mixed $instance, DbDriverInterface $dbDriverWrite)
    {
        $this->mapper = $mapper;
        $this->instance = $instance;
        $this->dbDriverWrite = $dbDriverWrite;
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
        return $fieldMap?->getUpdateFunctionValue($value, $this->instance, $this->dbDriverWrite->getDbHelper()) ?? $value;
    }
}
