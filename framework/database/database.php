<?php

/**
*
* uniBox 2.0 (winterspring)\n
* (c) Media Soma GbR
*
* 0.1   01.03.2005  jn      1st release\n
* 0.11  14.07.2005  jn      removed unnecessary brackets and added some comments\n
* 0.12  26.01.2006  jn      added database name
*
*/

class ub_db
{
    /**
    * class version
    * 
    */
    const version = '0.1.0';

    /**
    * singleton class instance
    * 
    */
    private static $instance = null;

    /**
    * returns class version
    * 
    * @return       version-number (float)
    */
    public static function get_version()
    {
        return ub_db::version;
    }

    /**
    * returns class instance
    * 
    * @return       ub_db (object)
    */
    public static function get_instance($db_conf = null)
    {        
        if (self::$instance === null)
        {
            // include database configuration if none was passed
            if ($db_conf === null)
            {
	            if (!is_readable(DIR_FRAMEWORK_DATABASE.'config.php'))
	                throw new ub_exception_runtime('database configuration not found - run install/install.php5 if uniBox isn\'t yet installed');
	            require(DIR_FRAMEWORK_DATABASE.'config.php');
            }

            if ($db_conf === null)
                throw new ub_exception_runtime('invalid database configuration');

            // build classname and instanciate the class
            $classname = 'ub_db_layer_'.$db_conf->type;
            self::$instance = new $classname($db_conf);
            unset($db_conf);
        }
        return self::$instance;
    }

    /**
    * determines if the query was manipulating data
    * 
    * @param        $result		sql query (string)
    * @return       result (bool)
    */
    public static function is_manipulating($result)
    {
        $commands = 'INSERT|UPDATE|DELETE|REPLACE|CREATE|DROP|LOAD DATA|SELECT .* INTO|COPY|ALTER|GRANT|REVOKE|LOCK|UNLOCK';
        if (preg_match('/^\s*"?(' . $commands . ')\s+/i', $result))
            return true;
        return false;
    }
}

?>