<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DbFunctionsInterface;
use ByJG\MicroOrm\Literal\HexUuidLiteral;
use ByJG\MicroOrm\Literal\Literal;

class MapperFunctions
{
    const STANDARD = [MapperFunctions::class, 'standard'];
    const READ_ONLY = [MapperFunctions::class, 'readOnly'];
    const UPDATE_BINARY_UUID = [MapperFunctions::class, 'updateBinaryUuid'];
    const SELECT_BINARY_UUID = [MapperFunctions::class, 'selectBinaryUuid'];
    const NOW_UTC = [MapperFunctions::class, 'nowUtc'];

    public static function standard(mixed $value): mixed
    {
        if (empty($value) && $value !== 0 && $value !== '0' && $value !== false) {
            return null;
        }
        return $value;
    }

    public static function readOnly(): bool
    {
        return false;
    }

    public static function updateBinaryUuid(mixed $value): mixed
    {
        if (empty($value)) {
            return null;
        }
        if (!($value instanceof Literal)) {
            $value = new HexUuidLiteral($value);
        }
        return $value;
    }

    public static function selectBinaryUuid(mixed $value): ?string
    {
        $fieldValue = HexUuidLiteral::getFormattedUuid($value, false);
        if (is_null($fieldValue)) {
            return null;
        }
        return $fieldValue;
    }

    public static function nowUtc(mixed $value, mixed $instance, DbFunctionsInterface $helper): mixed
    {
        return new Literal($helper->sqlDate('Y-m-d H:i:s'));
    }

}