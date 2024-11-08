<?php

namespace ByJG\MicroOrm;

use Closure;

/**
 * Class MapperClosure
 * @deprecated Use MapperFunctions instead
 */
class MapperClosure
{
    public static function standard(): Closure
    {
        return function ($value) {
            return MapperFunctions::standard($value);
        };
    }

    public static function readOnly(): Closure
    {
        return function () {
            return MapperFunctions::readOnly();
        };
    }

    public static function updateBinaryUuid(): Closure
    {
        return (function ($value, $instance) {
            return MapperFunctions::updateBinaryUuid($value);
        });
    }

    public static function selectBinaryUuid(): Closure
    {
        return (function ($value, $instance) {
            return MapperFunctions::selectBinaryUuid($value);
        });
    }

}