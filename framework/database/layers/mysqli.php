<?php

define('FETCHMODE_NUM', MYSQLI_NUM);
define('FETCHMODE_ASSOC', MYSQLI_ASSOC);
define('FETCHMODE_BOTH', MYSQLI_BOTH);

/**
*
* uniBox 2.0 (winterspring)\n
* (c) Media Soma GbR
*
* 1.0   15.11.2006  jn      copied from ub_db_layer_mysql, marked as final
*
*/

class ub_db_layer_mysqli extends ub_db_layer
{
    /**
    * class version
    * 
    */
    const version = '0.1.0';

    /**
    * returns class version
    * 
    * @return       version-number (float)
    */
    public static function get_version()
    {
        return ub_db_layer_mysqli::version;
    }

    /**
    * gets the errormessage of the last error
    * 
    */
    public function get_error_message()
    {
        $errno = @$this->connection->errno;
        if ($errno != 0)
            return @$this->connection->error.' ('.$errno.')';
    }

    /**
    * class constructor that connects the server and selects the database
    * 
    * @param        $conf           database configuration
    */
    public function __construct($db_conf)
    {
    	// check if database functions are available
        if (!function_exists('mysqli_connect'))
        	throw new ub_exception_runtime('database module not available \'mysqli\'');

        // connect server and unset password
        // TODO: add database port/socket support
        $this->connection = new mysqli($db_conf->server, $db_conf->user, $db_conf->pass, $db_conf->name);
        unset($db_conf->pass);

        if (!$this->connection)
            throw new ub_exception_runtime('can\'t connect to '.$db_conf->server);

        // get server info
        $this->version = $this->connection->server_info;
        $this->schema = $db_conf->name;

        // set client encoding to utf8 - if mysqli 4.1 or higher
        if (version_compare($this->version, '4.1') >= 0)
            if ($this->connection->character_set_name() != 'utf8')
            	if (!@$this->connection->query('SET NAMES \'utf8\''))
                	throw new ub_exception_runtime('failed to set client encoding');
    }

	public function __get($name)
	{
		if ($name == 'tools')
			$this->tools = new ub_db_tools_mysql();
		return $this->$name;
	}

	public function __isset($name)
	{
		if ($name == 'tools')
			$this->tools = new ub_db_tools_mysql();
		return isset($this->$name);
	}

	public function free($result)
	{
		$result->free();
	}

    /**
    * begins a transaction
    * 
    */
    public function begin_transaction($disable_foreign_keys = false)
    {
        if ($disable_foreign_keys)
            if (!@$this->connection->query('SET FOREIGN_KEY_CHECKS = 0'))
                throw new ub_exception_database('failed to disable foreign key checks');
        $this->transaction_disable_foreign_keys = $disable_foreign_keys;

        if ($this->transaction || $this->connection->autocommit(false))
            return ($this->transaction = true);
        else
            throw new ub_exception_database('failed to start transaction');
    }

    /**
    * commit transaction
    * 
    */
    public function commit()
    {
        if ($this->transaction_disable_foreign_keys)
            if (!@$this->connection->query('SET FOREIGN_KEY_CHECKS = 1'))
                throw new ub_exception_database('failed to re-enable foreign key checks');
        
        if ($this->transaction && @$this->connection->commit() && @$this->connection->autocommit(true))
        {
            $this->transaction = false;
            return true;
        }
        else
            throw new ub_exception_database('called commit outside an transaction or commit failed');
    }

    /**
    * rollback transaction
    * 
    */
    public function rollback($message = null, $trl_args = array(), $return = false)
    {
        $db_error = $this->get_error_message();

        // re-enable foreign key checks if disabled by begin_transaction
        if ($this->transaction_disable_foreign_keys)
            if (!@$this->connection->query('SET FOREIGN_KEY_CHECKS = 1'))
                throw new ub_exception_database('failed to re-enable foreign key checks on transaction rollback');

        if ($this->transaction && @$this->connection->rollback() && @$this->connection->autocommit(true))
        {
            if ($message !== null)
            {
                $msg = new ub_message(MSG_ERROR);
                $msg->add_text($message, $trl_args);
                if (DEBUG > 0 && !empty($this->error))
                {
                    $msg->add_newline(2);
                    $msg->add_text($this->error, array(), false);
                }
                if (DEBUG > 1)
                {
                    $msg->add_newline(2);
                    $msg->add_text($db_error, array(), false);
                    $msg->add_newline(2);
                    $msg->add_text($this->get_last_query(), array(), false);
                }
                if (!$return)
                    $msg->display();
                else
                    return $msg;
            }
            $this->transaction = false;
        }
        else
            throw new ub_exception_database('called rollback outside of transaction or rollback failed');
    }

    /**
    * sends the sql string to the database and returns a result object
    * 
    * @param        $sql_string         sql query (string)
    * @param        $error_message      error message (string)
    * @param        $parse_sql_string   evaluate sql string (bool)
    * @param        $suppress_errors    suppress database errors (bool)
    * @return       result object or false on error (object/bool)
    * 
    */
    public function query($sql_string, $error_message = null, $parse_sql_string = false, $suppress_errors = false)
    {
        $this->error = $error_message;

    	if ($parse_sql_string)
    		$sql_string = $this->parse_sql_string($sql_string);

        // set the limit from variables if given
        if ($this->limit_count != null)
        {
            if ($this->limit_from != null)
            {
                $sql_string .= ' LIMIT '.$this->limit_from.', '.$this->limit_count;
                $this->limit_from = null;
            }
            else
                $sql_string .= ' LIMIT '.$this->limit_count;
        	$this->limit_count = null;
        }

        // set last query to used sql statement
        $this->last_query = $sql_string;

        // query the database
        $result = @$this->connection->query($sql_string, MYSQLI_STORE_RESULT);

        // check result
        if ($result)
        {
            $this->error = null;
            // try if the result is a resource (SELECT, SHOW, DESCRIBE or EXPLAIN)
            if ($result instanceof mysqli_result)
                $result = new ub_db_result($this, $result);
            else
                $result = true;
        }
        else
        {
            if ($this->transaction)
                $exception_type = 'ub_exception_transaction';
            else
                $exception_type = 'ub_exception_database';

            // return query result if errors are suppressed
            if ($suppress_errors)
                return false;
            else
            {
                if ($this->error === null)
                    throw new $exception_type('query failed');
                else
                	throw new $exception_type('query failed ('.$this->error.')');
            }
        }

        $this->limit_count = null;
        $this->limit_from = null;
        $this->query_count++;

        return $result;
    }

    /**
    * returns the affected rows variable
    * 
    * @return       count of affected rows - only on manipulating queries (int)
    */
    public function affected_rows()
    {
        // try if last query was a manipulating one and count the affected rows
        if (ub_db::is_manipulating($this->last_query))
            return $this->connection->affected_rows;
        else
            return 0;
    }

    /**
    * returns the number of rows affected by the given result resource
    * 
    * @param        $result         result resource to count values from (resource)
    * @return       count of found rows - only on searching queries (int)
    */
    public function num_rows($result, $query)
    {
        // try if last query was not a manipulating one and count the rows
        if (!ub_db::is_manipulating($query))
        {
            $rows = @$result->num_rows;
            if ($rows === null)
                return 0;
            return $rows;
        }
        else
            return 0;
    }

    /**
    * fetch the requested row from the given result resource
    * 
    * @param        $result         result resource to fetch values from (resource)
    * @param        $data           variable to put data in (array, passed by reference)
    * @param        $fetchmode      fetch type (string)
    * @param        $row_to_fetch   what row to fetch (integer)
    * @return       result (bool)
    */
    public function fetch_into($result, &$data, $fetchmode = FETCHMODE_NUM, $row_to_fetch = null)
    {
        if ($data = @$result->fetch_array($fetchmode))
            return true;
        else
            return false;
    }

    /**
    * adds limit values to objectvars
    * 
    * @param        $result         result resource to seek data in (resource)
    * @param        $position       dataset to go to (integer)
    */
    public function data_seek($result, $position = 0)
    {
        // jump to dataset
        if (!($result = @$result->data_seek($position)))
            throw new ub_exception_database('failed to seek dataset: '.$position);
        else
            return $result;
    }

    /**
    * cleans the variable for database insertion
    * 
    * @param        $string         string to be cleaned (string)
    * @return       cleaned string (string)
    */
    public function cleanup($string, $input = false)
    {
    	if (is_array($string))
		{
			foreach ($string AS $key => $value)
				$string[$key] = $this->cleanup($value);
			return $string;
		}
		else
		{
	    	if (get_magic_quotes_gpc() && $input)
	    		$string = stripslashes($string);
	
			// TODO: quote smart!
	        return $this->connection->real_escape_string($string);
		}
    }

    /**
    * returns the id of the last inserted dataset
    * 
    * @return       last inserted id (int)
    */
    public function last_insert_id()
    {
        return $this->connection->insert_id;
    }
}

?>