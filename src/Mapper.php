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

    protected $fieldMap = [];

    /**
     * Mapper constructor.
     *
     * @param string $entity
     * @param string $table
     * @param string $primaryKey
     * @throws \Exception
     */
    public function __construct($entity, $table, $primaryKey)
    {
        if (!class_exists($entity)) {
            throw new \Exception("Entity '$entity' does not exists");
        }
        $this->entity = $entity;
        $this->table = $table;
        $this->primaryKey = $primaryKey;
    }

    /**
     * @param string $property
     * @param string $fieldName
     * @return $this
     */
    public function addFieldMap($property, $fieldName)
    {
        $this[$property] = $fieldName;

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

    
}
