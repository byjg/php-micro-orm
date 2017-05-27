<?php
/**
 * User: jg
 * Date: 19/05/17
 * Time: 16:54
 */

namespace ByJG\MicroOrm;


class ORMHelper
{
    public static function processLiteral($sql, &$params)
    {
        if (!is_array($params)) {
            return $sql;
        }

        foreach ($params as $field => $param) {
            if ($param instanceof Literal) {
                $literalValue = $param->getLiteralValue();
                $sql = preg_replace(
                    [
                        "/\\[\\[$field\\]\\]/",
                        "/:$field([^\\d\\w]|$)/"
                    ],
                    [
                        $literalValue,
                        "$literalValue\$1"
                    ],
                    $sql
                );
                unset($params[$field]);
            }
        }

        return $sql;
    }
}