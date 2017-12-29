<?php

/**
 * Created by PhpStorm.
 * User: Galek
 * Date: 12.6.2017
 * Time: 14:36
 */

namespace Doctrine\DBAL\Driver\Firebird;

use Doctrine\DBAL\Driver\Statement;
use Traversable;

class FirebirdStatement implements \IteratorAggregate, Statement
{
    /**
     * @var resource
     */
    private $_stmt;

    /**
     * @var array
     */
    private $_bindParam = [];

    /**
     * @var string Name of the default class to instantiate when fetch mode is \PDO::FETCH_CLASS
     */
    private $defaultFetchClass = '\stdClass';

    /**
     * @var string Constructor arguments for the default class to instantiate when fetch mode is \PDO::FETCH_CLASS.
     */
    private $defaultFetchClassCtorArgs = [];

    /**
     * @var int
     */
    private $_defaultFetchMode = \PDO::FETCH_BOTH;

    /**
     * Indicates whether the statement is in the state when fetching results is possible
     *
     * @var bool
     */
    private $result = false;

    private $_result;

    /**
     * @param resource $stmt
     */
    public function __construct($stmt)
    {
        $this->_stmt = $stmt;
    }

    /**
     * Retrieve an external iterator
     *
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     *
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator()
    {
        $data = $this->fetchAll();
        return new \ArrayIterator($data);
    }

    /**
     * Closes the cursor, enabling the statement to be executed again.
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function closeCursor()
    {
        if (!$this->_stmt) {
            return false;
        }

        if (!$this->_result) {
            return false;
        }

        $this->_bindParam = [];

        if (!ibase_free_result($this->_result)) {
            return false;
        }

        $this->result = false;

        return true;
    }

    /**
     * Returns the number of columns in the result set
     *
     * @return int The number of columns in the result set represented
     *                 by the PDOStatement object. If there is no result set,
     *                 this method should return 0.
     */
    public function columnCount()
    {
        return 0;
    }

    /**
     * Sets the fetch mode to use while iterating this statement.
     *
     * @param int $fetchMode The fetch mode must be one of the PDO::FETCH_* constants.
     * @param mixed $arg2
     * @param mixed $arg3
     *
     * @return bool
     *
     * @see PDO::FETCH_* constants.
     */
    public function setFetchMode($fetchMode, $arg2 = null, $arg3 = null)
    {
        $this->_defaultFetchMode = $fetchMode;
        $this->defaultFetchClass = $arg2 ? $arg2 : $this->defaultFetchClass;
        $this->defaultFetchClassCtorArgs = $arg3 ? (array) $arg3 : $this->defaultFetchClassCtorArgs;

        return true;
    }

    /**
     * Returns the next row of a result set.
     *
     * @param int|null $fetchMode Controls how the next row will be returned to the caller.
     *                            The value must be one of the PDO::FETCH_* constants,
     *                            defaulting to PDO::FETCH_BOTH.
     *
     * @return mixed The return value of this method on success depends on the fetch mode. In all cases, FALSE is
     *               returned on failure.
     *
     * @see PDO::FETCH_* constants.
     */
    public function fetch($fetchMode = null)
    {
        if (!$this->result) {
            return false;
        }

        $fetchMode = $fetchMode ?: $this->_defaultFetchMode;

        $query = $this->_result;

        switch ($fetchMode) {
            case \PDO::FETCH_BOTH:
                return ibase_fetch_row($query, IBASE_TEXT);
            case \PDO::FETCH_ASSOC:
                return ibase_fetch_assoc($query, IBASE_TEXT);
            case \PDO::FETCH_OBJ:
                return ibase_fetch_object($query, IBASE_TEXT);
            case \PDO::FETCH_NUM:
                return ibase_fetch_row($query, IBASE_TEXT);
            default:
                throw new FirebirdException('Given Fetch-Style ' . $fetchMode . ' is not supported.');
        }
    }

    /**
     * Returns an array containing all of the result set rows.
     *
     * @param int|null $fetchMode Controls how the next row will be returned to the caller.
     *                            The value must be one of the PDO::FETCH_* constants,
     *                            defaulting to PDO::FETCH_BOTH.
     *
     * @return array
     *
     * @see PDO::FETCH_* constants.
     */
    public function fetchAll($fetchMode = null)
    {
        $rows = [];

        switch ($fetchMode) {
            case \PDO::FETCH_COLUMN:
                while ($row = $this->fetchColumn()) {
                    $rows[] = $row;
                }
                break;
            default:
                while ($row = $this->fetch($fetchMode)) {
                    $rows[] = $row;
                }
        }

        return $rows;
    }

    /**
     * Returns a single column from the next row of a result set or FALSE if there are no more rows.
     *
     * @param int $columnIndex 0-indexed number of the column you wish to retrieve from the row.
     *                         If no value is supplied, PDOStatement->fetchColumn()
     *                         fetches the first column.
     *
     * @return string|bool A single column in the next row of a result set, or FALSE if there are no more rows.
     */
    public function fetchColumn($columnIndex = 0)
    {
        $row = $this->fetch(\PDO::FETCH_COLUMN);

        if ($row === false) {
            return false;
        }

        return isset($row[$columnIndex]) ? $row[$columnIndex] : null;
    }

    /**
     * Binds a value to a corresponding named (not supported by mysqli driver, see comment below) or positional
     * placeholder in the SQL statement that was used to prepare the statement.
     *
     * As mentioned above, the named parameters are not natively supported by the mysqli driver, use executeQuery(),
     * fetchAll(), fetchArray(), fetchColumn(), fetchAssoc() methods to have the named parameter emulated by doctrine.
     *
     * @param mixed $param Parameter identifier. For a prepared statement using named placeholders,
     *                     this will be a parameter name of the form :name. For a prepared statement
     *                     using question mark placeholders, this will be the 1-indexed position of the parameter.
     * @param mixed $value The value to bind to the parameter.
     * @param int $type Explicit data type for the parameter using the PDO::PARAM_* constants.
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function bindValue($param, $value, $type = null)
    {
        return $this->bindParam($param, $value, $type);
    }

    /**
     * Binds a PHP variable to a corresponding named (not supported by mysqli driver, see comment below) or question
     * mark placeholder in the SQL statement that was use to prepare the statement. Unlike PDOStatement->bindValue(),
     * the variable is bound as a reference and will only be evaluated at the time
     * that PDOStatement->execute() is called.
     *
     * As mentioned above, the named parameters are not natively supported by the mysqli driver, use executeQuery(),
     * fetchAll(), fetchArray(), fetchColumn(), fetchAssoc() methods to have the named parameter emulated by doctrine.
     *
     * Most parameters are input parameters, that is, parameters that are
     * used in a read-only fashion to build up the query. Some drivers support the invocation
     * of stored procedures that return data as output parameters, and some also as input/output
     * parameters that both send in data and are updated to receive it.
     *
     * @param mixed $column Parameter identifier. For a prepared statement using named placeholders,
     *                      this will be a parameter name of the form :name. For a prepared statement using
     *                      question mark placeholders, this will be the 1-indexed position of the parameter.
     * @param mixed $variable Name of the PHP variable to bind to the SQL statement parameter.
     * @param int|null $type Explicit data type for the parameter using the PDO::PARAM_* constants. To return
     *                       an INOUT parameter from a stored procedure, use the bitwise OR operator to set the
     *                       PDO::PARAM_INPUT_OUTPUT bits for the data_type parameter.
     * @param int|null $length You must specify maxlength when using an OUT bind
     *                         so that PHP allocates enough memory to hold the returned value.
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function bindParam($column, &$variable, $type = null, $length = null)
    {
        $this->_bindParam[$column] = &$variable;

        return true;
    }

    /**
     * Fetches the SQLSTATE associated with the last operation on the statement handle.
     *
     * @see Doctrine_Adapter_Interface::errorCode()
     *
     * @return string The error code string.
     */
    public function errorCode()
    {
        return ibase_errcode();
    }

    /**
     * Fetches extended error information associated with the last operation on the statement handle.
     *
     * @see Doctrine_Adapter_Interface::errorInfo()
     *
     * @return array The error info array.
     */
    public function errorInfo()
    {
        return [
            ibase_errmsg(),
            ibase_errcode(),
        ];
    }

    /**
     * Executes a prepared statement
     *
     * If the prepared statement included parameter markers, you must either:
     * call PDOStatement->bindParam() to bind PHP variables to the parameter markers:
     * bound variables pass their value as input and receive the output value,
     * if any, of their associated parameter markers or pass an array of input-only
     * parameter values.
     *
     *
     * @param array|null $params An array of values with as many elements as there are
     *                           bound parameters in the SQL statement being executed.
     *
     * @return bool TRUE on success or FALSE on failure.
     */
    public function execute($params = null)
    {
        if (!$this->_stmt) {
            return false;
        }

        if ($params === null) {
            ksort($this->_bindParam);
            $params = [];

            foreach ($this->_bindParam as $column => $value) {
                $params[] = $value;
            }
        }

        if (!empty($params)) {
            if (!is_array($params)) {
                return ibase_execute($this->_stmt, $params);
            }
            array_unshift($params, $this->_stmt);
            $this->_result = @call_user_func_array('ibase_execute', $params);
        } else {
            $this->_result = ibase_execute($this->_stmt);
        }

        if ($this->_result === false) {
            throw new FirebirdException(ibase_errmsg());
        }

        $this->result = true;

        return $this->_result;
    }

    /**
     * Returns the number of rows affected by the last DELETE, INSERT, or UPDATE statement
     * executed by the corresponding object.
     *
     * If the last SQL statement executed by the associated Statement object was a SELECT statement,
     * some databases may return the number of rows returned by that statement. However,
     * this behaviour is not guaranteed for all databases and should not be
     * relied on for portable applications.
     *
     * @return int The number of rows.
     */
    public function rowCount()
    {
        return 100;
    }
}
