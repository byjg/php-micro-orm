<?php

namespace Tests;

use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\AnyDataset\Db\Factory;
use ByJG\MicroOrm\FieldMapping;
use ByJG\MicroOrm\Mapper;
use ByJG\MicroOrm\Query;
use ByJG\MicroOrm\Repository;
use PHPUnit\Framework\TestCase;
use Tests\Model\Customer;

class RepositoryAliasTest extends TestCase
{

    const URI = 'mysql://root:password@127.0.0.1';

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

    public function setUp(): void
    {
        $this->dbDriver = Factory::getDbInstance(self::URI);
        $this->dbDriver->execute('create database if not exists testmicroorm;');
        $this->dbDriver = Factory::getDbInstance(self::URI . "/testmicroorm");

        $this->dbDriver->execute('create table customers (
            id integer primary key  auto_increment,
            customer_name varchar(45),
            customer_age int);'
        );
        $this->dbDriver->execute("insert into customers (customer_name, customer_age) values ('John Doe', 40)");
        $this->dbDriver->execute("insert into customers (customer_name, customer_age) values ('Jane Doe', 37)");
        $this->customerMapper = new Mapper(Customer::class, 'customers', 'id');
        $this->customerMapper->addFieldMapping(FieldMapping::create('customerName')->withFieldName('customer_Name'));
        $this->customerMapper->addFieldMapping(FieldMapping::create('notInTable')->dontSyncWithDb());


        $fieldMap = FieldMapping::create('Age')
            ->withFieldName('customer_Age')
            ->withFieldAlias('custAge');
        $this->customerMapper->addFieldMapping($fieldMap);

        $this->repository = new Repository($this->dbDriver, $this->customerMapper);
    }

    public function tearDown(): void
    {
        $this->dbDriver->execute('drop table if exists customers;');
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

    public function testQueryWithAlias()
    {
        $query = $this->repository->queryInstance()
            ->fields(
                [
                    'id',
                    'customer_name',
                    'customer_age as custage'
                ]
            )
            ->where('id = 1');

        $customerList = $this->repository->getByQuery($query);

        $this->assertEquals(1, count($customerList));

        $customer = $customerList[0];

        $this->assertEquals(1, $customer->getId());
        $this->assertEquals('John Doe', $customer->getCustomerName());
        $this->assertEquals(40, $customer->getAge());
    }

    public function testQueryWithAlias2()
    {
        $query = Query::getInstance()
            ->table('customers')
            ->fields([
                $this->repository->getMapper()
            ])
            ->where('id = 1');

        $customerList = $this->repository->getByQuery($query);

        $this->assertEquals(1, count($customerList));

        $customer = $customerList[0];

        $this->assertEquals(1, $customer->getId());
        $this->assertEquals('John Doe', $customer->getCustomerName());
        $this->assertEquals(40, $customer->getAge());
    }

}
