<?php

namespace ByJG\MicroOrm\Literal;

use ByJG\MicroOrm\Exception\InvalidArgumentException;

class HexUuidLiteral extends Literal
{
    protected string $prefix = "X'";
    protected string $suffix = "'";

    protected string $formattedUuid;

    public function __construct(HexUuidLiteral|string $value)
    {
        parent::__construct($this->binaryString($value));
    }
    
    public static function create(mixed $value): mixed
    {
        if ($value instanceof HexUuidLiteral) {
            return $value;
        }

        if (is_array($value)) {
            foreach ($value as $i => $val) {
                $value[$i] = HexUuidLiteral::create($val);
            }
            return $value;
        }

        
        if (empty(HexUuidLiteral::getFormattedUuid($value, false))) {
            return $value;
        }
        
        return new HexUuidLiteral($value);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function binaryString(HexUuidLiteral|string $value): string
    {
        if ($value instanceof HexUuidLiteral) {
            $value = $value->formattedUuid;
        } else {
            $value = $this->formatUuid($value);
        }
        $this->formattedUuid = $value;
        return $this->prefix . preg_replace('/[^0-9A-Fa-f]/', '', $this->formattedUuid) . $this->suffix;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function formatUuid(HexUuidLiteral|string $item): string
    {
        if (empty($item)) {
            return $item;
        }

        $originalItem = $item;

        if ($item instanceof HexUuidLiteral) {
            $pattern = "/^" . $item->prefix . '(.*)' . $item->suffix . "$/";
        } else {
            $pattern = "/^" . $this->prefix . '(.*)' . $this->suffix . "$/";
        }
        $pattern = str_replace('\\', '\\\\', $pattern);

        if ($item instanceof Literal) {
            $item = preg_replace($pattern, "$1", $item->__toString());
        }

        if (preg_match($pattern, $item, $matches)) {
            $item = $matches[1];
        }

        if (is_string($item) && !ctype_print($item) && strlen($item) === 16) {
            $item = bin2hex($item);
        }

        if (preg_match("/^\w{8}-?\w{4}-?\w{4}-?\w{4}-?\w{12}$/", $item)) {
            $item = preg_replace("/^(\w{8})-?(\w{4})-?(\w{4})-?(\w{4})-?(\w{12})$/", "$1-$2-$3-$4-$5", $item);
        } else {
            throw new InvalidArgumentException("Invalid UUID format");
        }

        return strtolower($item);
    }

    public static function getUuidFromLiteral(HexUuidLiteral $literal): string
    {
        return $literal->formattedUuid;
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function getFormattedUuid(HexUuidLiteral|string $item, bool $throwErrorIfInvalid = true): ?string
    {
        try {
            $class = static::class;
            $literal = new $class($item);
        } catch (InvalidArgumentException $ex) {
            if ($throwErrorIfInvalid) {
                throw $ex;
            }
            return null;
        }
        return $literal->formattedUuid;
    }
}
