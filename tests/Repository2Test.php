<?php

namespace Test;

use ByJG\AnyDataset\DbDriverInterface;
use ByJG\AnyDataset\Factory;
use ByJG\MicroOrm\Mapper;
use ByJG\MicroOrm\Repository;
use ByJG\Util\Uri;

require_once 'Customer.php';

// backward compatibility
if (!class_exists('\PHPUnit\Framework\TestCase')) {
    class_alias('\PHPUnit_Framework_TestCase', '\PHPUnit\Framework\TestCase');
}

class Repository2Test extends \PHPUnit\Framework\TestCase
{

    const URI='sqlite:///tmp/teste.db';

    /**
     * @var Mapper
     */
    protected $customerMapper;

    /**
     * @var DbDriverInterface
     */
    protected $dbDriver;

    /**
     * @var Repository
     */
    protected $repository;

    public function setUp()
    {
        $this->dbDriver = Factory::getDbRelationalInstance(self::URI);

        $this->dbDriver->execute('create table customers (
            id integer primary key  autoincrement, 
            customer_name varchar(45), 
            customer_age int);'
        );
        $this->dbDriver->execute("insert into customers (customer_name, customer_age) values ('John Doe', 40)");
        $this->dbDriver->execute("insert into customers (customer_name, customer_age) values ('Jane Doe', 37)");
        $this->customerMapper = new Mapper(Customer::class, 'customers', 'id');
        $this->customerMapper->addFieldMap('customername', 'customer_name');
        $this->customerMapper->addFieldMap('age', 'customer_age');

        $this->repository = new Repository($this->dbDriver, $this->customerMapper);
    }

    public function tearDown()
    {
        $uri = new Uri(self::URI);
        unlink($uri->getPath());
    }

    public function testGet()
    {
        $customer = $this->repository->get(1);
        $this->assertEquals(1, $customer->getId());
        $this->assertEquals('John Doe', $customer->getCustomerName());
        $this->assertEquals(40, $customer->getAge());

        $customer = $this->repository->get(2);
        $this->assertEquals(2, $customer->getId());
        $this->assertEquals('Jane Doe', $customer->getCustomerName());
        $this->assertEquals(37, $customer->getAge());
    }

    public function testInsert()
    {
        $customer = new Customer();
        $customer->setCustomerName('Bla99991919');
        $customer->setAge(50);

        $this->assertEquals(null, $customer->getId());
        $this->repository->save($customer);
        $this->assertEquals(3, $customer->getId());

        $customer2 = $this->repository->get(3);

        $this->assertEquals(3, $customer2->getId());
        $this->assertEquals('Bla99991919', $customer2->getCustomerName());
        $this->assertEquals(50, $customer2->getAge());
    }
}
