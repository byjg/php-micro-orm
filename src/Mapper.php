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

    protected $entity;
    protected $table;
    protected $primaryKey;
    protected $keygenFunction = null;

    const FIELDMAP_FIELD = 'fieldname';
    const FIELDMAP_UPDATEMASK = 'updatemask';
    const FIELDMAP_SELECTMASK = 'selectmask';

    protected $fieldMap = [];

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
     * @param \Closure $updateMask
     * @param \Closure $selectMask
     * @return $this
     */
    public function addFieldMap($property, $fieldName, \Closure $updateMask = null, \Closure $selectMask = null)
    {
        if (empty($selectMask)) {
            $selectMask = function ($field) {
                return $field;
            };
        }

        $this->fieldMap[$property] = [
            self::FIELDMAP_FIELD => $fieldName,
            self::FIELDMAP_UPDATEMASK => $updateMask,
            self::FIELDMAP_SELECTMASK => $selectMask
        ];

        return $this;
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
}
