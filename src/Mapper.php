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
    private $preserveCasename = false;

    /**
     * Mapper constructor.
     *
     * @param string $entity
     * @param string $table
     * @param string $primaryKey
     * @param \Closure $keygenFunction
     * @param bool $preserveCasename
     * @throws \Exception
     */
    public function __construct($entity, $table, $primaryKey, \Closure $keygenFunction = null, $preserveCasename = false)
    {
        if (!class_exists($entity)) {
            throw new \Exception("Entity '$entity' does not exists");
        }
        $this->entity = $entity;
        $this->table = $table;
        $this->preserveCasename = $preserveCasename;
        $this->primaryKey = $this->prepareField($primaryKey);
        $this->keygenFunction = $keygenFunction;
    }

    public function prepareField($field)
    {
        if (!$this->preserveCasename) {
            if (!is_array($field)) {
                return strtolower($field);
            }

            $result = [];
            foreach ($field as $key => $value) {
                $result[strtolower($key)] = $value;
            }
            return $result;
        }

        return $field;
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

        $this->fieldMap[$this->prepareField($property)] = [
            self::FIELDMAP_FIELD => $this->prepareField($fieldName),
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
        $this->fieldAlias[$this->prepareField($fieldName)] = $this->prepareField($alias);
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

        $property = $this->prepareField($property);

        if (!isset($this->fieldMap[$property])) {
            return null;
        }

        $fieldMap = $this->fieldMap[$property];

        if (empty($key)) {
            return $fieldMap;
        }

        return $fieldMap[$key];
    }

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
        return function ($value, $instance) {
            return $value;
        };
    }
}
