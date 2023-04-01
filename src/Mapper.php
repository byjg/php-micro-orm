<?php

namespace ByJG\MicroOrm;

use ByJG\MicroOrm\Exception\InvalidArgumentException;
use ByJG\MicroOrm\Exception\OrmModelInvalidException;

class Mapper
{

    const FIELDMAP_FIELD = 'fieldname';
    const FIELDMAP_UPDATEMASK = 'updatemask';
    const FIELDMAP_SELECTMASK = 'selectmask';

    private $entity;
    private $table;
    private $primaryKey;
    private $keygenFunction = null;
    private $fieldMap = [];
    private $fieldAlias = [];
    private $preserveCasename = false;

    /**
     * Mapper constructor.
     *
     * @param string $entity
     * @param string $table
     * @param string $primaryKey
     * @param \Closure $keygenFunction
     * @param bool $preserveCasename
     * @throws \ByJG\MicroOrm\Exception\OrmModelInvalidException
     */
    public function __construct(
        $entity,
        $table,
        $primaryKey,
        \Closure $keygenFunction = null,
        $preserveCasename = false
    ) {
        if (!class_exists($entity)) {
            throw new OrmModelInvalidException("Entity '$entity' does not exists");
        }
        $primaryKey = (array)$primaryKey;

        $this->entity = $entity;
        $this->table = $table;
        $this->preserveCasename = $preserveCasename;
        $this->primaryKey = array_map([$this, 'fixFieldName'], $primaryKey);
        $this->keygenFunction = $keygenFunction;
    }

    protected function fixFieldName($field)
    {
        if (!$this->preserveCasename) {
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

    /**
     * @param string $property
     * @param string $fieldName
     * @param \Closure|null|bool $updateMask
     * @param \Closure $selectMask
     * @return $this
     * @throws InvalidArgumentException
     */
    public function addFieldMap($property, $fieldName, \Closure $updateMask = null, \Closure $selectMask = null)
    {
        if (empty($selectMask)) {
            $selectMask = Mapper::defaultClosure();
        }

        if (empty($updateMask)) {
            $updateMask = Mapper::defaultClosure();
        }

        if (!is_null($updateMask) && !($updateMask instanceof \Closure)) {
            throw new InvalidArgumentException('UpdateMask must be a \Closure or NULL');
        }

        $this->fieldMap[$this->fixFieldName($property)] = [
            self::FIELDMAP_FIELD => $this->fixFieldName($fieldName),
            self::FIELDMAP_UPDATEMASK => $updateMask,
            self::FIELDMAP_SELECTMASK => $selectMask
        ];

        return $this;
    }

    /**
     * @param $fieldName
     * @param $alias
     */
    public function addFieldAlias($fieldName, $alias)
    {
        $this->fieldAlias[$this->fixFieldName($fieldName)] = $this->fixFieldName($alias);
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
    public function isPreserveCasename()
    {
        return $this->preserveCasename;
    }

    /**
     * @param string|null $property
     * @param string|null $key
     * @return array
     */
    public function getFieldMap($property = null, $key = null)
    {
        if (empty($property)) {
            return $this->fieldMap;
        }

        $property = $this->fixFieldName($property);

        if (!isset($this->fieldMap[$property])) {
            return null;
        }

        $fieldMap = $this->fieldMap[$property];

        if (empty($key)) {
            return $fieldMap;
        }

        return $fieldMap[$key];
    }

    /**
     * @param null $fieldName
     * @return array|mixed|null
     */
    public function getFieldAlias($fieldName = null)
    {
        if (empty($fieldName)) {
            return $this->fieldAlias;
        }
        
        if (!isset($this->fieldAlias[$fieldName])) {
            return null;
        }

        return $this->fieldAlias[$fieldName];
    }

    /**
     * @return mixed|null
     */
    public function generateKey()
    {
        if (empty($this->keygenFunction)) {
            return null;
        }

        $func = $this->keygenFunction;

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
