<?php

namespace ByJG\MicroOrm;

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

}