<?php

namespace ByJG\MicroOrm\Attributes;

use Attribute;
use ByJG\MicroOrm\FieldMapping;
use ByJG\MicroOrm\Mapper;
use ByJG\MicroOrm\MapperClosure;
use Closure;

#[Attribute(Attribute::TARGET_PROPERTY)]
class FieldReadOnlyAttribute extends FieldAttribute
{
    public function __construct(
        bool $primaryKey = null,
        string $fieldName = null,
        string $fieldAlias = null,
        bool $syncWithDb = null,
        Closure $selectFunction = null
    )
    {
        parent::__construct($primaryKey, $fieldName, $fieldAlias, $syncWithDb, MapperClosure::readOnly(), $selectFunction);
    }
}
