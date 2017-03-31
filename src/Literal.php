<?php
/**
 * User: jg
 * Date: 30/03/17
 * Time: 22:26
 */

namespace ByJG\MicroOrm;

class Literal
{
    protected $literalValue;

    /**
     * Literal constructor.
     *
     * @param $literalValue
     */
    public function __construct($literalValue)
    {
        $this->literalValue = $literalValue;
    }

    /**
     * @return mixed
     */
    public function getLiteralValue()
    {
        return $this->literalValue;
    }

    /**
     * @param mixed $literalValue
     */
    public function setLiteralValue($literalValue)
    {
        $this->literalValue = $literalValue;
    }
}
