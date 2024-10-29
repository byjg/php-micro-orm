<?php

namespace ByJG\MicroOrm;

use ByJG\MicroOrm\Literal\HexUuidLiteral;
use ByJG\MicroOrm\Literal\Literal;
use Closure;

class MapperClosure
{
    public static function standard(): Closure
    {
        return function ($value) {
            if (empty($value) && $value !== 0 && $value !== '0' && $value !== false) {
                return null;
            }
            return $value;
        };
    }

    public static function readOnly(): Closure
    {
        return function () {
            return false;
        };
    }

    public static function updateBinaryUuid(): Closure
    {
        return (function ($value, $instance) {
            if (empty($value)) {
                return null;
            }
            if (!($value instanceof Literal)) {
                $value = new HexUuidLiteral($value);
            }
            return $value;
        });
    }

    public static function selectBinaryUuid(): Closure
    {
        return (function ($value, $instance) {
            $fieldValue = HexUuidLiteral::getFormattedUuid($value, false);
            if (is_null($fieldValue)) {
                return null;
            }
            return $fieldValue;
        });
    }

}