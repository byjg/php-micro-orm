<?php
/**
 * Created by PhpStorm.
 * User: jg
 * Date: 21/06/16
 * Time: 13:55
 */

namespace ByJG\MicroOrm;

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

    /**
     * Mapper constructor.
     *
     * @param string $entity
     * @param string $table
     * @param string $primaryKey
     * @param \Closure $keygenFunction
     * @throws \Exception
     */
    public function __construct($entity, $table, $primaryKey, \Closure $keygenFunction = null)
    {
        if (!class_exists($entity)) {
            throw new \Exception("Entity '$entity' does not exists");
        }
        $this->entity = $entity;
        $this->table = $table;
        $this->primaryKey = $primaryKey;
        $this->keygenFunction = $keygenFunction;
    }

    /**
     * @param string $property
     * @param string $fieldName
     * @param \Closure|null|bool $updateMask
     * @param \Closure $selectMask
     * @return $this
     */
    public function addFieldMap($property, $fieldName, $updateMask = false, \Closure $selectMask = null)
    {
        if (empty($selectMask)) {
            $selectMask = Mapper::defaultClosure();
        }

        if ($updateMask === false) {
            $updateMask = Mapper::defaultClosure();
        }

        if (!is_null($updateMask) && !($updateMask instanceof \Closure)) {
            throw new \InvalidArgumentException('UpdateMask must be a \Closure or NULL');
        }

        $this->fieldMap[$property] = [
            self::FIELDMAP_FIELD => $fieldName,
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
        $this->fieldAlias[$fieldName] = $alias;
    }

    /**
     * @return string
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
     * @return string
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
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

        $fieldMap = $this->fieldMap[$property];

        if (empty($key)) {
            return $fieldMap;
        }

        return $fieldMap[$key];
    }

    public function getFieldAlias($fieldName)
    {
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
        return function ($value, $instance) {
            return $value;
        };
    }
}
