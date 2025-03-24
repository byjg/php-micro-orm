<?php

namespace ByJG\MicroOrm\Literal;

use Override;

class Literal implements LiteralInterface
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
    #[Override]
    public function getLiteralValue(): mixed
    {
        return $this->literalValue;
    }

    /**
     * @param mixed $literalValue
     */
    #[Override]
    public function setLiteralValue(mixed $literalValue): void
    {
        $this->literalValue = $literalValue;
    }

    public function __toString(): string
    {
        return $this->getLiteralValue();
    }
}
