<?php

namespace Tests\Model;

use ByJG\MicroOrm\Attributes\FieldAttribute;
use ByJG\MicroOrm\Attributes\TableMySqlUuidPKAttribute;
use ByJG\MicroOrm\Literal\LiteralInterface;

#[TableMySqlUuidPKAttribute(tableName: 'usersuuid')]
class UsersWithUuidKey
{
    #[FieldAttribute(primaryKey: true)]
    protected string|LiteralInterface|null $Id = null;

    #[FieldAttribute(fieldName: 'name')]
    protected ?string $name = null;

    /**
     * @return string|LiteralInterface|null
     */
    public function getId(): string|LiteralInterface|null
    {
        return $this->Id;
    }

    /**
     * @param string|LiteralInterface|null $Id
     */
    public function setId(string|LiteralInterface|null $Id): void
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
