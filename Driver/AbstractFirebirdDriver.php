<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Galek
 * Date: 12.6.2017
 * Time: 13:49
 */

namespace App\Model\Doctrine\DBAL\Driver;

use App\Model\Doctrine\DBAL\Platforms\FirebirdPlatform;
use App\Model\Doctrine\DBAL\Schema\FirebirdSchemaManager;
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
