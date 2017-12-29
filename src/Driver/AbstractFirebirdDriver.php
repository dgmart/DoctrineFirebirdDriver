<?php

/**
 * Created by PhpStorm.
 * User: Galek
 * Date: 12.6.2017
 * Time: 13:49
 */

namespace Doctrine\DBAL\Driver;

use Doctrine\DBAL\Platforms\FirebirdPlatform;
use Doctrine\DBAL\Schema\FirebirdSchemaManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;

abstract class AbstractFirebirdDriver implements Driver
{
    /**
     * {@inheritdoc}
     */
    public function getDatabase(Connection $conn)
    {
        $params = $conn->getParams();

        return $params['dbname'];
    }

    public function getDatabasePlatform()
    {
        return new FirebirdPlatform();
    }

    public function getSchemaManager(Connection $conn)
    {
        return new FirebirdSchemaManager($conn);
    }
}
