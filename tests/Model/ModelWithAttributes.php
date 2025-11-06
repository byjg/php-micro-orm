<?php

namespace Tests\Model;

use ByJG\MicroOrm\Attributes\FieldAttribute;
use ByJG\MicroOrm\Attributes\TableAttribute;
use ByJG\MicroOrm\Trait\CreatedAt;
use ByJG\MicroOrm\Trait\DeletedAt;
use ByJG\MicroOrm\Trait\UpdatedAt;

#[TableAttribute("info")]
class ModelWithAttributes
{
    use CreatedAt;
    use DeletedAt;
    use UpdatedAt;

    #[FieldAttribute(primaryKey: true, fieldName: "id")]
    protected $pk;
    #[FieldAttribute]
    public $iduser;

    #[FieldAttribute(fieldName: "property")]
    public $value;

    /**
     * @return mixed
     */
    public function getPk()
    {
        return $this->pk;
    }

    /**
     * @param mixed $pk
     */
    public function setPk($pk)
    {
        $this->pk = $pk;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->pk;
    }

    /**
     * @return mixed
     */
    public function getIdUser()
    {
        return $this->iduser;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

}