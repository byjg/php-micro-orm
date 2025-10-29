<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\Db\DbDriverInterface;
use ByJG\AnyDataset\Db\Factory;
use ByJG\MicroOrm\Exception\TransactionException;
use InvalidArgumentException;

class TransactionManager
{

    /**
     * @var DbDriverInterface[]
     */
    protected static array $connectionList = [];

    /**
     * It has an active transaction?
     *
     * @var bool
     */
    protected static bool $transaction = false;

    /**
     * Add or reuse a connection
     *
     * @param string $uriString
     * @return DbDriverInterface
     */
    public function addConnection(string $uriString): DbDriverInterface
    {
        $dbDriver = Factory::getDbInstance($uriString);
        $this->addDbDriver($dbDriver);
        return self::$connectionList[$uriString];
    }

    public function addDbDriver(DbDriverInterface $dbDriver): void
    {
        $uriString = $dbDriver->getUri()->__toString();

        if (!isset(self::$connectionList[$uriString])) {
            self::$connectionList[$uriString] = $dbDriver;

            if (self::$transaction) {
                self::$connectionList[$uriString]->beginTransaction();
            }
        } elseif (self::$connectionList[$uriString] !== $dbDriver) {
            throw new InvalidArgumentException("The connection already exists with a different instance");
        }
    }

    public function addRepository(Repository $repository): void
    {
        $this->addDbDriver($repository->getExecutor()->getDriver());
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count(self::$connectionList);
    }

    /**
     * Start a database transaction with the opened connections
     *
     * @throws TransactionException
     */
    public function beginTransaction(): void
    {
        if (self::$transaction) {
            throw new TransactionException("Transaction Already Started");
        }

        self::$transaction = true;
        foreach (self::$connectionList as $dbDriver) {
            $dbDriver->beginTransaction();
        }
    }


    /**
     * Commit all open transactions
     *
     * @throws TransactionException
     */
    public function commitTransaction(): void
    {
        if (!self::$transaction) {
            throw new TransactionException("There is no Active Transaction");
        }

        self::$transaction = false;
        foreach (self::$connectionList as $dbDriver) {
            $dbDriver->commitTransaction();
        }
    }


    /**
     * Rollback all open transactions
     *
     * @throws TransactionException
     */
    public function rollbackTransaction(): void
    {
        if (!self::$transaction) {
            throw new TransactionException("There is no Active Transaction");
        }

        self::$transaction = false;
        foreach (self::$connectionList as $dbDriver) {
            $dbDriver->rollbackTransaction();
        }
    }

    /**
     * Destroy all connections
     */
    public function destroy(): void
    {
        foreach (self::$connectionList as $dbDriver) {
            if (self::$transaction) {
                $dbDriver->commitTransaction();
            }
        }
        self::$transaction = false;
        self::$connectionList = [];
    }
}
