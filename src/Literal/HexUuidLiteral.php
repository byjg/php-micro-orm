<?php

namespace ByJG\MicroOrm\Literal;

use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\AnyDataset\Db\SqlDialect\DblibDialect;
use ByJG\AnyDataset\Db\SqlDialect\PgsqlDialect;
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

    /**
     * A new version 7 UUID (RFC 9562), formatted as XXXXXXXX-XXXX-7XXX-YXXX-XXXXXXXXXXXX.
     *
     * The first 48 bits are the Unix time in milliseconds and the rest is random, so a key
     * created later sorts after the earlier ones and a new row goes to the end of the index.
     * It tells when it was created: for a secret token use a random (version 4) UUID instead.
     */
    public static function v7(): string
    {
        $bytes = substr(pack('J', (int)floor(microtime(true) * 1000.0)), 2) . random_bytes(10);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x70);   // version 7
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);   // RFC 9562 variant

        return (string)self::getFormattedUuid($bytes);
    }

    /**
     * The UUID literal in the syntax of the executor's database: a native uuid on PostgreSQL,
     * 0x... for a binary(16) on SQL Server, and X'...' for a binary(16) on MySQL and SQLite
     * (also the default when there is no executor).
     *
     * @throws InvalidArgumentException
     */
    public static function forDatabase(?DatabaseExecutor $executor, LiteralInterface|string $value): HexUuidLiteral
    {
        $dialect = $executor?->getHelper();
        $class = match (true) {
            $dialect instanceof PgsqlDialect => PostgresUuidLiteral::class,
            $dialect instanceof DblibDialect => SqlServerUuidLiteral::class,
            default => HexUuidLiteral::class,
        };

        if ($value instanceof HexUuidLiteral && get_class($value) === $class) {
            return $value;
        }
        return new $class($value);
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
