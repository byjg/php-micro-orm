<?php

namespace Tests\Model;

use ByJG\MicroOrm\Attributes\FieldAttribute;
use ByJG\MicroOrm\Attributes\TableAttribute;
use ByJG\MicroOrm\Trait\ActiveRecord;

#[TableAttribute(tableName: "users", validator: RecordingValidator::class)]
class ActiveRecordWithValidator
{
    use ActiveRecord;

    #[FieldAttribute(primaryKey: true, fieldName: "id")]
    protected $id;

    #[FieldAttribute]
    public $name;

    #[FieldAttribute]
    public $createdate;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }
}
