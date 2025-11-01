<?php

namespace ByJG\MicroOrm\Attributes;

use Attribute;
use ByJG\MicroOrm\MapperFunctions\FormatSelectUuidMapper;
use ByJG\MicroOrm\MapperFunctions\FormatUpdateUuidMapper;

#[Attribute(Attribute::TARGET_PROPERTY)]
class FieldUuidAttribute extends FieldAttribute
{
    public function __construct(
        ?bool   $primaryKey = null,
        ?string $fieldName = null,
        ?string $fieldAlias = null,
        ?bool   $syncWithDb = null
    )
    {
        parent::__construct($primaryKey, $fieldName, $fieldAlias, $syncWithDb, FormatUpdateUuidMapper::class, FormatSelectUuidMapper::class);
    }
}
