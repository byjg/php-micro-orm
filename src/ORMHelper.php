<?php

namespace ByJG\MicroOrm;

use ByJG\MicroOrm\Literal\LiteralInterface;

class ORMHelper
{
    /**
     * @param string $sql
     * @param array|null $params
     * @return string
     */
    public static function processLiteral(string $sql, ?array &$params = null): string
    {
        if (empty($params)) {
            return $sql;
        }

        foreach ($params as $field => $param) {
            if ($param instanceof LiteralInterface) {
                $literalValue = $param->getLiteralValue();
                $sql = (string)preg_replace(
                    [
                        "/\[\[$field]]/",
                        "/:$field(\W|$)/"
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
