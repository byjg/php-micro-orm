<?php

namespace Tests\Model;

use ByJG\MicroOrm\Attributes\FieldAttribute;
use ByJG\MicroOrm\Attributes\TableAttribute;

#[TableAttribute("info")]
class ModelWithAttributes
{
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

}