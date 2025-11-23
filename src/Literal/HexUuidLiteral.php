<?php

namespace ByJG\MicroOrm\Literal;

use ByJG\MicroOrm\Exception\InvalidArgumentException;

class HexUuidLiteral extends Literal
{
    protected string $prefix = "X'";
    protected string $suffix = "'";

    protected string $formattedUuid;

    public function __construct(LiteralInterface|string $value)
    {
        parent::__construct($this->binaryString($value));
    }

    public static function create(mixed $value): mixed
    {
        if ($value instanceof HexUuidLiteral || is_null($value)) {
            return $value;
        }

        if (is_array($value)) {
            foreach ($value as $i => $val) {
                $value[$i] = HexUuidLiteral::create($val);
            }
            return $value;
        }

        try {
            return new HexUuidLiteral($value);
        } catch (InvalidArgumentException $ex) {
            return $value;
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    public function binaryString(LiteralInterface|string $value): string
    {
        if ($value instanceof HexUuidLiteral) {
            $value = $value->formattedUuid;
        } else {
            $value = self::getFormattedUuid($value);
        }
        if ($value === null) {
            throw new InvalidArgumentException("UUID value cannot be null or empty");
        }
        $this->formattedUuid = $value;
        return $this->prefix . preg_replace('/[^0-9A-Fa-f]/', '', $this->formattedUuid) . $this->suffix;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function formatUuid(): ?string
    {
        return HexUuidLiteral::getFormattedUuid($this->getLiteralValue());
    }

    public static function getUuidFromLiteral(HexUuidLiteral $literal): string
    {
        return $literal->formattedUuid;
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function getFormattedUuid(LiteralInterface|string|null $item, bool $throwErrorIfInvalid = true, $default = null): ?string
    {
        if ($item instanceof LiteralInterface) {
            $item = $item->__toString();
        }

        if (is_null($item) || $item === '') {
            return null;
        }

        if (strlen($item) === 16) {
            $item = bin2hex($item);
        }

        $pattern = strtoupper((string)preg_replace('/(^0[xX]|[^A-Fa-f0-9])/', '', $item));

        if (strlen($pattern) === 32) {
            return preg_replace("/^(\w{8})(\w{4})(\w{4})(\w{4})(\w{12})$/", "$1-$2-$3-$4-$5", $pattern);
        } elseif ($throwErrorIfInvalid) {
            throw new InvalidArgumentException("Invalid UUID format");
        }

        return $default;
    }
}
