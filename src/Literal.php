<?php

namespace ByJG\MicroOrm;

class Literal
{
    protected mixed $literalValue;

    /**
     * Literal constructor.
     *
     * @param mixed $literalValue
     */
    public function __construct(mixed $literalValue)
    {
        $this->literalValue = $literalValue;
    }

    /**
     * @return mixed
     */
    public function getLiteralValue(): mixed
    {
        return $this->literalValue;
    }

    /**
     * @param mixed $literalValue
     */
    public function setLiteralValue(mixed $literalValue): void
    {
        $this->literalValue = $literalValue;
    }

    public function __toString()
    {
        return $this->getLiteralValue();
    }
}
