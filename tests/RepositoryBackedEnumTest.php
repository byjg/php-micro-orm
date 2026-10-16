<?php

namespace Tests;

use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\AnyDataset\Db\Factory;
use ByJG\MicroOrm\Repository;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Tests\Model\Order;
use Tests\Model\OrderStatus;

/**
 * A property typed with a backed enum is stored as the enum's value and loaded back as the enum.
 */
#[RequiresPhpExtension('pdo_sqlite')]
class RepositoryBackedEnumTest extends TestCase
{
    protected DatabaseExecutor $executor;
    protected Repository $repository;

    public function setUp(): void
    {
        $this->executor = DatabaseExecutor::using(Factory::getDbInstance('sqlite://' . sys_get_temp_dir() . '/micro-orm-enum.db'));
        $this->executor->execute('DROP TABLE IF EXISTS orders');
        $this->executor->execute('CREATE TABLE orders (id INTEGER PRIMARY KEY AUTOINCREMENT, status VARCHAR(10))');
        $this->repository = new Repository($this->executor, Order::class);
    }

    public function tearDown(): void
    {
        $this->executor->execute('DROP TABLE IF EXISTS orders');
    }

    public function testTheEnumIsStoredAsItsValue(): void
    {
        $order = new Order();
        $order->status = OrderStatus::Pending;
        $this->repository->save($order);

        $this->assertSame('PENDING', $this->executor->getScalar('SELECT status FROM orders WHERE id = :id', ['id' => $order->id]));

        $order->status = OrderStatus::Paid;
        $this->repository->save($order);

        $this->assertSame('PAID', $this->executor->getScalar('SELECT status FROM orders WHERE id = :id', ['id' => $order->id]));
    }

    public function testTheValueIsLoadedAsTheEnum(): void
    {
        $this->executor->execute("INSERT INTO orders (status) VALUES ('PAID')");

        $order = $this->repository->get(1);

        $this->assertSame(OrderStatus::Paid, $order->status);
    }

    public function testAModelWithAnEnumFiltersByItsValue(): void
    {
        $this->executor->execute("INSERT INTO orders (status) VALUES ('PENDING'), ('PAID'), ('PAID')");

        $filter = new Order();
        $filter->status = OrderStatus::Paid;
        $orders = $this->repository->getByQuery($this->repository->queryInstance($filter));

        $this->assertCount(2, $orders);
        $this->assertSame(OrderStatus::Paid, $orders[0]->status);
    }
}
