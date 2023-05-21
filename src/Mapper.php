<?php

namespace ByJG\MicroOrm;

use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\MicroOrm\Exception\OrmModelInvalidException;

class Mapper
{

    private $entity;
    private $table;
    private $primaryKey;
    private $primaryKeySeedFunction = null;

    /**
     * @var FieldMapping[]
     */
    private $fieldMap = [];
    private $preserveCaseName = false;

    /**
     * Mapper constructor.
     *
     * @param string $entity
     * @param string $table
     * @param string $primaryKey
     * @throws \ByJG\MicroOrm\Exception\OrmModelInvalidException
     */
    public function __construct(
        $entity,
        $table,
        $primaryKey
    ) {
        if (!class_exists($entity)) {
            throw new OrmModelInvalidException("Entity '$entity' does not exists");
        }
        $primaryKey = (array)$primaryKey;

        $this->entity = $entity;
        $this->table = $table;
        $this->primaryKey = array_map([$this, 'fixFieldName'], $primaryKey);
    }

    public function withPrimaryKeySeedFunction(\Closure $primaryKeySeedFunction)
    {
        $this->primaryKeySeedFunction = $primaryKeySeedFunction;
        return $this;
    }

    public function withPreserveCaseName()
    {
        $this->preserveCaseName = true;
        return $this;
    }

    protected function fixFieldName($field)
    {
        if (is_null($field)) {
            return null;
        }

        if (!$this->preserveCaseName) {
            return strtolower($field);
        }

        return $field;
    }

    public function prepareField(array $fieldList)
    {
        foreach ($fieldList as $key => $value) {
            $result[$this->fixFieldName($key)] = $value;
        }
        return $result;
    }

    public function addFieldMapping(FieldMapping $fieldMapping)
    {
        $propertyName = $this->fixFieldName($fieldMapping->getPropertyName());
        $fieldName = $this->fixFieldName($fieldMapping->getFieldName());
        $fieldAlias = $this->fixFieldName($fieldMapping->getFieldAlias());
        $this->fieldMap[$propertyName] = $fieldMapping
            ->withFieldName($fieldName)
            ->withFieldAlias($fieldAlias)
        ;
        return $this;
    }

    /**
     * @param string $property
     * @param string $fieldName
     * @param \Closure|null|bool $updateFunction
     * @param \Closure $selectFunction
     * @return $this
     * @throws InvalidArgumentException
     * @deprecated Use addFieldMapping instead
     */
    public function addFieldMap($property, $fieldName, \Closure $updateFunction = null, \Closure $selectFunction = null)
    {
        $fieldMapping = FieldMapping::create($property)
            ->withFieldName($fieldName);

        if (!is_null($updateFunction)) {
            $fieldMapping->withUpdateFunction($updateFunction);
        }
        if (!is_null($selectFunction)) {
            $fieldMapping->withSelectFunction($selectFunction);
        }

        return $this->addFieldMapping($fieldMapping);
    }

    /**
     * @return object
     */
    public function getEntity()
    {
        $class = $this->entity;
        return new $class();
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @return array
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * @return bool
     */
    public function isPreserveCaseName()
    {
        return $this->preserveCaseName;
    }

    /**
     * @param string|null $property
     * @param string|null $key
     * @return FieldMapping[]|FieldMapping|null
     */
    public function getFieldMap($property = null)
    {
        if (empty($property)) {
            return $this->fieldMap;
        }

        $property = $this->fixFieldName($property);

        if (!isset($this->fieldMap[$property])) {
            return null;
        }

        $fieldMap = $this->fieldMap[$property];

        return $fieldMap;
    }

    /**
     * @param null $fieldName
     * @return array|mixed|null
     */
    public function getFieldAlias($fieldName = null)
    {
        $fieldMapVar = $this->getFieldMap($fieldName);
        if (empty($fieldMap)) {
            return null;
        }

        return $fieldMapVar->getFieldAlias();
    }

    /**
     * @return mixed|null
     */
    public function generateKey()
    {
        if (empty($this->primaryKeySeedFunction)) {
            return null;
        }

        $func = $this->primaryKeySeedFunction;

        return $func();
    }

    public static function defaultClosure()
    {
        return function ($value) {
            if (empty($value)) {
                return null;
            }
            return $value;
        };
    }

    public static function doNotUpdateClosure()
    {
        return function () {
            return false;
        };
    }
}
