<?php

namespace ByJG\MicroOrm;

class SqlObject
{
    protected string $sql;

    protected array $parameters;

    protected SqlObjectEnum $type;

    public function __construct(string $sql, array $parameters = [], $type = SqlObjectEnum::SELECT)
    {
        $this->sql = $sql;
        $this->parameters = $parameters;
        $this->type = $type;
    }

    public function getSql(): string
    {
        return $this->sql;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getParameter($key)
    {
        return $this->parameters[$key] ?? null;
    }

    public function getType(): SqlObjectEnum
    {
        return $this->type;
    }
}