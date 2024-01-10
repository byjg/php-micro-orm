<?php

namespace ByJG\MicroOrm;

class SqlObject
{
    protected string $sql;

    protected array $parameters;

    public function __construct(string $sql, array $parameters = [])
    {
        $this->sql = $sql;
        $this->parameters = $parameters;
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

}