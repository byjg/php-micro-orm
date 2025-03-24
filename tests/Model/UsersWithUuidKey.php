<?php

namespace Tests\Model;

use ByJG\MicroOrm\Attributes\FieldAttribute;
use ByJG\MicroOrm\Attributes\TableSqliteUuidPKAttribute;
use ByJG\MicroOrm\Literal\Literal;

#[TableSqliteUuidPKAttribute(tableName: 'usersuuid')]
class UsersWithUuidKey
{
    #[FieldAttribute(primaryKey: true)]
    protected string|Literal|null $Id = null;

    #[FieldAttribute(fieldName: 'name')]
    protected ?string $name = null;

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
