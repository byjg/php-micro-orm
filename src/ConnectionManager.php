<?php

namespace ByJG\MicroOrm;

use ByJG\AnyDataset\DbDriverInterface;
use ByJG\AnyDataset\Factory;
use ByJG\MicroOrm\Exception\OrmBeforeInvalidException;
use ByJG\MicroOrm\Exception\OrmInvalidFieldsException;
use ByJG\MicroOrm\Exception\TransactionException;
use ByJG\Serializer\BinderObject;
use ByJG\Util\Uri;

class ConnectionManager
{

    /**
     * @var DbDriverInterface[]
     */
    protected static $connectionList = [];

    /**
     * @var bool
     */
    protected static $transaction = false;

    /**
     * @param $uriString
     * @return \ByJG\AnyDataset\DbDriverInterface
     */
    public function addConnection($uriString)
    {
        if (!isset(self::$connectionList[$uriString])) {
            self::$connectionList[$uriString] = Factory::getDbRelationalInstance($uriString);

            if (self::$transaction) {
                self::$connectionList[$uriString]->beginTransaction();
            }
        }

        return self::$connectionList[$uriString];
    }

    public function beginTransaction()
    {
        if (self::$transaction) {
            throw new TransactionException("Transaction Already Started");
        }

        self::$transaction = true;
        foreach (self::$connectionList as $dbDriver) {
            $dbDriver->beginTransaction();
        }
    }


    public function commitTransaction()
    {
        if (!self::$transaction) {
            throw new TransactionException("There is no Active Transaction");
        }

        self::$transaction = false;
        foreach (self::$connectionList as $dbDriver) {
            $dbDriver->commitTransaction();
        }
    }


    public function rollbackTransaction()
    {
        if (!self::$transaction) {
            throw new TransactionException("There is no Active Transaction");
        }

        self::$transaction = false;
        foreach (self::$connectionList as $dbDriver) {
            $dbDriver->rollbackTransaction();
        }
    }


}
