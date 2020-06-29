<?php

namespace ByJG\MicroOrm;

class ORMHelper
{
    /**
     * @param string $sql
     * @param array $params
     * @return string
     */
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
