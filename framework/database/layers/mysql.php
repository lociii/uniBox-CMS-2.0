<?php

define('FETCHMODE_NUM', MYSQL_NUM);
define('FETCHMODE_ASSOC', MYSQL_ASSOC);
define('FETCHMODE_BOTH', MYSQL_BOTH);

/**
*
* uniBox 2.0 (winterspring)\n
* (c) Media Soma GbR
*
* 1.0   15.11.2006  jn      splitted tools from layer, marked as final
*
*/

class ub_db_layer_mysql extends ub_db_layer
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
        return ub_db_layer_mysql::version;
    }

    /**
    * gets the errormessage of the last error
    * 
    */
    public function get_error_message()
    {
        $errno = @mysql_errno($this->connection);
        if ($errno != 0)
            return @mysql_error($this->connection).' ('.$errno.')';
    }

    /**
    * class constructor that connects the server and selects the database
    * 
    * @param        $conf           database configuration
    */
    public function __construct($db_conf)
    {
        // connect server and unset password
        // TODO: add database port/socket support
        $this->connection = @mysql_connect($db_conf->server, $db_conf->user, $db_conf->pass);
        unset($db_conf->pass);

        if (!$this->connection)
            throw new ub_exception_runtime('can\'t connect to '.$db_conf->server);

        // select database
        if (!mysql_select_db($db_conf->name, $this->connection))
            throw new ub_exception_runtime('failed to select database '.$db_conf->name);

        // get server info
        $this->version = mysql_get_server_info();
        $this->schema = $db_conf->name;

        // set client encoding to utf8 - if not done yet and mysql 4.1 or higher
        if (version_compare($this->version, '4.1') >= 0)
            if (mysql_client_encoding() != 'utf8')
            	if (!@mysql_query('SET NAMES \'utf8\'', $this->connection))
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
		mysql_free_result($result);
	}

    /**
    * begins a transaction
    * 
    */
    public function begin_transaction($disable_foreign_keys = false)
    {
        if ($disable_foreign_keys)
            if (!@mysql_query('SET FOREIGN_KEY_CHECKS = 0'))
                throw new ub_exception_database('failed to disable foreign key checks');
        $this->transaction_disable_foreign_keys = $disable_foreign_keys;

        if ($this->transaction || @mysql_query('BEGIN', $this->connection))
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
            if (!@mysql_query('SET FOREIGN_KEY_CHECKS = 1'))
                throw new ub_exception_database('failed to re-enable foreign key checks');
        
        if ($this->transaction && @mysql_query('COMMIT', $this->connection))
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
        $mysql_error = $this->get_error_message();

        // re-enable foreign key checks if disabled by begin_transaction
        if ($this->transaction_disable_foreign_keys)
            if (!@mysql_query('SET FOREIGN_KEY_CHECKS = 1'))
                throw new ub_exception_database('failed to re-enable foreign key checks on transaction rollback');

        if ($this->transaction && @mysql_query('ROLLBACK', $this->connection))
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
                    $msg->add_text($mysql_error, array(), false);
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
        $result = @mysql_query($sql_string, $this->connection);

        // check result
        if ($result)
        {
            $this->error = null;
            // try if the result is a resource (SELECT, SHOW, DESCRIBE or EXPLAIN)
            if (is_resource($result))
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
            return mysql_affected_rows($this->connection);
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
            $rows = @mysql_num_rows($result);
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
        if ($data = @mysql_fetch_array($result, $fetchmode))
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
        if (!($result = @mysql_data_seek($result, $position)))
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
	        return mysql_real_escape_string($string);
		}
    }

    /**
    * returns the id of the last inserted dataset
    * 
    * @return       last inserted id (int)
    */
    public function last_insert_id()
    {
        return mysql_insert_id($this->connection);
    }
}

?>