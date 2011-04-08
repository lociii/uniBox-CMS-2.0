<?php

/**
*
* uniBox 2.0 (winterspring)\n
* (c) Media Soma GbR
*
* 0.1     02.03.2005  jn      1st release\n
* 0.11    14.07.2005  jn      removed unnecessary brackets
*
*/

abstract class ub_db_layer
{
    /**
    * connection resource id
    * 
    */
    protected $connection;

    /**
    * unibox instance - only used by parse_sql
    * 
    */
    protected $unibox;

    /**
    * from which dataset to start
    * 
    */
    protected $limit_from = NULL;

    /**
    * how many datasets to process
    * 
    */
    protected $limit_count = NULL;

    /**
    * last query string
    * 
    */
    protected $last_query = '';

    /**
    * last error message
    * 
    */
    protected $error;

	/**
	 * total count of queries made
	 * 
	 */
	protected $query_count = 0;
	
    /**
    * current database's name
    * 
    */
    protected $schema = null;

    /**
    * indicates if within a transaction
    * 
    */
    protected $transaction = false;

    /**
    * indicates if foreign key checks are disabled within the transaction
    * 
    */
    protected $transaction_disable_foreign_keys = false;

    /**
    * returns the limit from variable
    * 
    * @return       limit from (int)
    */
    public function get_limit_from()
    {
        return $this->limit_from;
    }

    /**
    * returns the limit count variable
    * 
    * @return       limit count (int)
    */
    public function get_limit_count()
    {
        return $this->limit_count;
    }

    /**
    * returns the last query string
    * 
    * @return       query string (string)
    */
    public function get_last_query()
    {
        return $this->last_query;
    }

	/**
	 * returns the query count
	 * 
	 * @return 	   query count (int)
	 */
	public function get_query_count()
	{
		return $this->query_count;
	}

    /**
     * returns the current database name
     * 
     * @return     name (string)
     */
    public function get_schema()
    {
        return $this->schema;
    }

    /**
    * gets the errormessage of the last error
    * 
    */
    public function get_error_message()
    {
        throw new ub_exception_database('dbms is not able to perform \'get_error_message\'');
    }

    /**
    * class constructor that connects the server and selects the database
    * 
    * @param        $conf           database configuration
    */
    abstract public function __construct($conf);

    /**
    * begins a transaction
    * 
    */
    public function begin_transaction()
    {
        throw new ub_exception_database('dbms is not able to perform \'begin_transaction\'');
    }

    /**
    * commit transaction
    * 
    */
    public function commit()
    {
        throw new ub_exception_database('dbms is not able to perform \'commit\'');
    }

    /**
    * rollback transaction
    * 
    */
    public function rollback()
    {
        throw new ub_exception_database('dbms is not able to perform \'rollback\'');
    }

    /**
    * sends the sql string to the database and returns a result object
    * 
    * @param        $sql_string     sql query (string)
    * @param        $error_message  error message (string)
    */
    public function query($sql_string, $error_message = '')
    {
        throw new ub_exception_database('dbms is not able to perform \'query\'');
    }

    /**
    * returns the affected rows variable
    * 
    */
    public function affected_rows()
    {
        throw new ub_exception_database('dbms is not able to perform \'affected_rows\'');
    }

    /**
    * returns the number of rows affected by the given result resource
    * 
    * @param        $result         result resource to count values from (resource)
    */
    public function num_rows($result)
    {
        throw new ub_exception_database('dbms is not able to perform \'num_rows\'');
    }

    /**
    * fetch the requested row from the given result resource
    * 
    * @param        $result         result resource to fetch values from (resource)
    * @param        $data           variable to put data in (array, passed by reference)
    * @param        $fetchmode      fetch type (string)
    * @param        $row_to_fetch   what row to fetch (integer)
    */
    public function fetch_into($result, &$data, $fetchmode, $row_to_fetch = NULL)
    {
        throw new ub_exception_database('dbms is not able to perform \'fetch_into\'');
    }

    /**
    * sets beginning and end of fetching-interval
    * 
    * @param        $limit_from     dataset to show from (integer)
    * @param        $limit_count    how many dataset to show (integer)
    */
    public function limit_query($limit_from = NULL, $limit_count = NULL)
    {
        // add limit values to objectvars
        if ((int)$limit_count == $limit_count)
            $this->limit_count = $limit_count;
        if ((int)$limit_from == $limit_from)
            $this->limit_from = $limit_from;
    }

    /**
    * adds limit values to objectvars
    * 
    * @param        $result         result resource to seek data in (resource)
    * @param        $limit_from     dataset to show from (integer)
    */
    public function data_seek($result, $limit_from = NULL)
    {
        // limit result if limits are set
        if ($limit_from != NULL)
        {
            $data = array();
            $i = 0;
            $dataset_found = true;
            while ($i++ < $limit_from && $dataset_found)
            {
                $dataset_found = $this->fetch_into($result, $data);
                $data = array();
            }
        }
    }

    /**
    * cleans the variable for database insertion
    * 
    * @param        $string         string to be cleaned (string)
    */
    public function cleanup($string)
    {
        throw new ub_exception_database('dbms is not able to perform \'cleanup\'');
    }

    /**
    * returns the id of the last inserted dataset
    * 
    */
    public function last_insert_id()
    {
        throw new ub_exception_database('dbms is not able to perform \'last_insert_id\'');
    }

    /**
     * replaces table name constants and globally accessible variables
     * in the sql-string
     * 
     * @param		$sql_string			sql-string to be modified
     * @return		modified sql-string
     */
    public function parse_sql_string($sql_string)
    {
        $this->unibox = ub_unibox::get_instance();
        $matches = $replacements = $patterns = array();
        preg_match_all('/\$\$PHP: ?(.+)\$\$/i', $sql_string, $matches);
        foreach ($matches[1] as $key => $value)
        {
            $patterns[$key] = '/'.preg_quote($matches[0][$key], '/').'/';
            $replacements[$key] = eval('return '.$value.';');
        }
        return preg_replace($patterns, $replacements, $sql_string);
    }
}

class ub_db_result
{
    /**
    * results sql query
    * 
    */
    protected $query;

    /**
    * connection resource id
    * 
    */
    protected $connection;

    /**
    * database result object
    * 
    */
    protected $result;

    /**
    * from which dataset to start
    * 
    */
    protected $limit_from = NULL;

    /**
    * how many datasets to process
    * 
    */
    protected $limit_count = NULL;

    /**
    * how many datasets have been process yet
    * 
    */
    protected $row_counter = 0;

    /**
    * class constructor that initializes the result object
    * 
    * @param        $connection     ub_db_* database abstraction object
    * @param        $result         database result resource (resource)
    */
    public function __construct($connection, $result)
    {
        $this->connection = $connection;
        $this->limit_from = $connection->get_limit_from();
        $this->limit_count = $connection->get_limit_count();
        $this->connection = $connection;
        $this->result = $result;
        $this->query = $connection->get_last_query();
    }

    /**
    * returns the count of rows variable
    * 
    * @return       count of rows (integer)
    */
    public function num_rows()
    {
        return $this->connection->num_rows($this->result, $this->query);
    }

    /**
    * returns the count of rows variable
    * 
    * @return       count of rows (integer)
    */
    public function fetch_row($fetchmode = FETCHMODE_NUM)
    {
        // initalize marker
        $dataset_found = false;

        // go to the first requested dataset
        if ($this->limit_from != null)
            $this->connection->data_seek($this->result, $this->limit_from);

        // try if a limit count is set
        $data = array();
        if ($this->limit_count != null)
        {
            // try if we're still allowed to read new datasets
            if ($this->row_counter <= $this->limit_count)
                $dataset_found = $this->connection->fetch_into($this->result, $data, $fetchmode);
        }
        else
            $dataset_found = $this->connection->fetch_into($this->result, $data, $fetchmode);

        // return dataset if found
        if ($dataset_found)
        {
        	// increase number of read datasets
            $this->row_counter++;
            return $data;
        }
        return false;
    }
    
    public function goto($position = 0)
    {
        return $this->connection->data_seek($this->result, $position);
    }
    
    public function get_row_counter()
    {
    	return $this->row_counter;
    }

	public function free()
	{
		$this->connection->free($this->result);
	}
}

?>