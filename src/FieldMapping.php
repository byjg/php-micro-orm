<?php

namespace ByJG\MicroOrm;

class FieldMapping
{
    /**
     * @var string
     */
    private $fieldName;

    /**
     * @var string
     */
    private $updateFunction;

    /**
     * @var string
     */
    private $selectFunction;

    /**
     * @var string
     */
    private $propertyName;

    /**
     * @var string
     */
    private $fieldAlias;

    public static function create($propertyName)
    {
        return new FieldMapping($propertyName);
    }

    /**
     * FieldMapping constructor.
     * @param string $fieldName
     * @param string $updateFunction
     * @param string $selectFunction
     */
    public function __construct($propertyName)
    {
        $this->fieldName = $propertyName;
        $this->propertyName = $propertyName;

        $this->selectFunction = Mapper::defaultClosure();
        $this->updateFunction = Mapper::defaultClosure();
    }

    public function withUpdateFunction(\Closure $updateFunction)
    {
        $this->updateFunction = $updateFunction;
        return $this;
    }

    public function withSelectFunction(\Closure $selectFunction)
    {
        $this->selectFunction = $selectFunction;
        return $this;
    }

    public function withFieldName($fieldName)
    {
        $this->fieldName = $fieldName;
        return $this;
    }

    public function withFieldAlias($fieldAlias)
    {
        $this->fieldAlias = $fieldAlias;
        return $this;
    }



    /**
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }

    /**
     * @return string
     */
    public function getUpdateFunction()
    {
        return $this->updateFunction;
    }

    /**
     * @return string
     */
    public function getSelectFunction()
    {
        return $this->selectFunction;
    }

    public function getPropertyName()
    {
        return $this->propertyName;
    }

    public function getFieldAlias()
    {
        return $this->fieldAlias;
    }

    public function getSelectFunctionValue($value, $instance)
    {
        return call_user_func_array($this->selectFunction, [$value, $instance]);
    }

    public function getUpdateFunctionValue($value, $instance)
    {
        return call_user_func_array($this->updateFunction, [$value, $instance]);
    }
}