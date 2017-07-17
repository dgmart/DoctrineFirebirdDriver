<?php
/**
 * Created by PhpStorm.
 * User: Galek
 * Date: 12.6.2017
 * Time: 14:10
 */

namespace App\Model\Doctrine\DBAL\Driver\Firebird;


use App\Model\Doctrine\DBAL\Driver\AbstractFirebirdDriver;

class FirebirdDriver extends AbstractFirebirdDriver
{

	/**
	 * Attempts to create a connection with the database.
	 *
	 * @param array $params All connection parameters passed by the user.
	 * @param string|null $username The username to use when connecting.
	 * @param string|null $password The password to use when connecting.
	 * @param array $driverOptions The driver options to use when connecting.
	 *
	 * @return \Doctrine\DBAL\Driver\Connection The database connection.
	 */
	public function connect(array $params, $username = null, $password = null, array $driverOptions = [])
	{
		return new FirebirdConnection($params['dbname'], $username, $password);
	}

	/**


	 * Gets the name of the driver.
	 *
	 * @return string The name of the driver.
	 */
	public function getName()
	{
		return 'firebird';
	}
}
