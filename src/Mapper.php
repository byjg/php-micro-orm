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
     * @return $this
     */
    public function addFieldMap($property, $fieldName)
    {
        $this->fieldMap[$property] = $fieldName;

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
     * @return array
     */
    public function getFieldMap()
    {
        return $this->fieldMap;
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
