<?php

namespace Tests;

use ByJG\AnyDataset\Db\Interfaces\DbDriverInterface;
use ByJG\AnyDataset\Db\Factory;
use ByJG\Util\Uri;

class ConnectionUtil
{
    public static function getConnection(string $database): DbDriverInterface
    {
        $dbDriver = Factory::getDbInstance(ConnectionUtil::getUri());
        $dbDriver->execute("create database if not exists $database;");
        return Factory::getDbInstance(ConnectionUtil::getUri($database));
    }

    public static function getUri(?string $database = null): Uri
    {
        $host = getenv('MYSQL_TEST_HOST') ? getenv('MYSQL_TEST_HOST') : '127.0.0.1';
        $uri = new Uri("mysql://root:password@$host");

        if (empty($database)) {
            return $uri;
        }

        return $uri->withPath("/$database");
    }
}