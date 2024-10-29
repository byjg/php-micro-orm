<?php

namespace Tests\Model;

class Customer
{
    protected $id;
    protected $customer_name;
    protected $age;

    protected $notInTable;

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

    /**
     * @return mixed
     */
    public function getCustomerName()
    {
        return $this->customer_name;
    }

    /**
     * @param mixed $customer_name
     */
    public function setCustomerName($customer_name)
    {
        $this->customer_name = $customer_name;
    }

    /**
     * @return mixed
     */
    public function getAge()
    {
        return $this->age;
    }

    /**
     * @param mixed $age
     */
    public function setAge($age)
    {
        $this->age = $age;
    }

    /**
     * @return mixed
     */
    public function getNotInTable()
    {
        return $this->notInTable;
    }

    /**
     * @param mixed $notInTable
     */
    public function setNotInTable($notInTable)
    {
        $this->notInTable = $notInTable;
    }
}
