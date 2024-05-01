<?php

namespace Tests\Model;

use ByJG\MicroOrm\Attributes\FieldAttribute;
use ByJG\MicroOrm\Attributes\TableAttribute;

#[TableSeedAttribute]
class UsersWithAttribute
{
    #[FieldAttribute(primaryKey: true, fieldName: 'id')]
    protected $Id;

    #[FieldAttribute(fieldName: 'name')]
    protected $name;

    #[FieldAttribute(fieldName: 'createdate')]
    protected $createdate;

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

    /**
     * @return mixed
     */
    public function getCreatedate()
    {
        return $this->createdate;
    }

    /**
     * @param mixed $createdate
     */
    public function setCreatedate($createdate)
    {
        $this->createdate = $createdate;
    }
}
