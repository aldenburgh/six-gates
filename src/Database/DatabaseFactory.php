<?php

namespace SixGates\Database;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Connection;

class DatabaseFactory
{
    public static function create(array $config): Connection
    {
        $connectionParams = [
            'dbname' => $config['dbname'],
            'user' => $config['user'],
            'password' => $config['password'],
            'host' => $config['host'],
            'port' => $config['port'],
            'driver' => $config['driver'],
        ];

        $dbalConfig = new Configuration();

        return DriverManager::getConnection($connectionParams, $dbalConfig);
    }
}
