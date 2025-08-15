<?php

namespace Tests\Model;

use ByJG\MicroOrm\Attributes\FieldAttribute;
use ByJG\MicroOrm\Attributes\TableAttribute;
use ByJG\MicroOrm\Trait\ActiveRecord;

#[TableAttribute(
    tableName: "users",
    beforeInsert: TestInsertProcessor::class,
    beforeUpdate: TestUpdateProcessor::class
)]
class ActiveRecordWithProcessors
{
    use ActiveRecord;

    #[FieldAttribute(primaryKey: true, fieldName: "id")]
    protected $id;

    #[FieldAttribute]
    public $name;

    #[FieldAttribute]
    public $createdate;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
} 