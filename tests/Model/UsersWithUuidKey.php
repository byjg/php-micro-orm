<?php

namespace Tests\Model;

use ByJG\MicroOrm\Attributes\FieldAttribute;
use ByJG\MicroOrm\Attributes\TableMySqlUuidPKAttribute;
use ByJG\MicroOrm\Literal\Literal;

#[TableMySqlUuidPKAttribute(tableName: 'usersuuid')]
class UsersWithUuidKey
{
    #[FieldAttribute(primaryKey: true)]
    protected string|Literal|null $Id = null;

    #[FieldAttribute(fieldName: 'name')]
    protected ?string $name;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->Id;
    }

    /**
     * @param mixed $Id
     */
    public function setId($Id)
    {
        $this->Id = $Id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
