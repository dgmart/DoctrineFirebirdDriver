<?php

/**
 * Created by PhpStorm.
 * User: Galek
 * Date: 12.6.2017
 * Time: 14:12
 */

namespace Doctrine\DBAL\Driver\Firebird;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\ServerInfoAwareConnection;

class FirebirdConnection implements Connection, ServerInfoAwareConnection
{
    /**
     * @var resource
     */
    private $_conn = null;

    public function __construct($dbname, $username = null, $password = null, array $driverOptions = [])
    {
        $isPersistant = (isset($params['persistent']) && $params['persistent'] == true);

        if ($isPersistant) {
            $this->_conn = @ibase_pconnect($dbname, $username, $password);
            //$this->_conn = ibase_pconnect($dbname, $username, $password);
        } else {
            $this->_conn = @ibase_connect($dbname, $username, $password);
            //$this->_conn = ibase_connect($dbname, $username, $password);
        }

        //$this->_service = ibase_service_attach($dbname, $username, $password);
        if (!$this->_conn) {
            throw new FirebirdException(ibase_errmsg());
        }
    }

    /**
     * Prepares a statement for execution and returns a Statement object.
     *
     * @param string $prepareString
     *
     * @return \Doctrine\DBAL\Driver\Statement
     */
    public function prepare($prepareString)
    {
        $stmt = ibase_prepare($this->_conn, $prepareString);

        if (!$stmt) {
            throw new FirebirdException(ibase_errmsg());
        }

        return new FirebirdStatement($stmt);
    }

    /**
     * Executes an SQL statement, returning a result set as a Statement object.
     *
     * @return \Doctrine\DBAL\Driver\Statement
     */
    public function query()
    {
        $args = func_get_args();
        $sql = $args[0];
        $stmt = $this->prepare($sql);
        $stmt->execute();

        return $stmt;
    }

    /**
     * Quotes a string for use in a query.
     *
     * @param string $input
     * @param int $type
     *
     * @return string
     */
    public function quote($input, $type = \PDO::PARAM_STR)
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }
        $value = str_replace("'", "''", $value);
        return "'" . $value . "'";
    }

    /**
     * Executes an SQL statement and return the number of affected rows.
     *
     * @param string $statement
     *
     * @return int
     */
    public function exec($statement)
    {
        $stmt = ibase_execute($this->_conn, $statement);

        if ($stmt === false) {
            throw new FirebirdException(ibase_errmsg());
        }

        return count($stmt);
    }

    /**
     * Returns the ID of the last inserted row or sequence value.
     *
     * @param string|null $name
     *
     * @return string
     */
    public function lastInsertId($name = null)
    {
        return ibase_gen_id($name, 0, $this->_conn);
    }

    /**
     * Initiates a transaction.
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function beginTransaction()
    {
        // TODO: Implement beginTransaction() method.
        //$this->in
    }

    /**
     * Commits a transaction.
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function commit()
    {
        // TODO: Implement commit() method.
    }

    /**
     * Rolls back the current transaction, as initiated by beginTransaction().
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function rollBack()
    {
        // TODO: Implement rollBack() method.
    }

    /**
     * Returns the error code associated with the last operation on the database handle.
     *
     * @return string|null The error code, or null if no operation has been run on the database handle.
     */
    public function errorCode()
    {
        return ibase_errcode();
    }

    /**
     * Returns extended error information associated with the last operation on the database handle.
     *
     * @return array
     */
    public function errorInfo()
    {
        return [
            ibase_errmsg(),
            ibase_errcode(),
        ];
    }

    /**
     * Returns the version number of the database server connected to.
     *
     * @return string
     */
    public function getServerVersion()
    {
        // TODO: Implement getServerVersion() method.
    }

    /**
     * Checks whether a query is required to retrieve the database server version.
     *
     * @return bool True if a query is required to retrieve the database server version, false otherwise.
     */
    public function requiresQueryForServerVersion()
    {
        // TODO: Implement requiresQueryForServerVersion() method.
    }
}
