<?php

/**
*
* uniBox 2.0 (winterspring)
*
* Media Soma - Gestaltung und interaktive Technologien
* Raphael Fischer, Oliver Kieffer und Jens Nistler GbR
* Rathausstrasse 75-79
* 66333 Voelklingen
*
* (c) Media Soma GbR     -    jens nistler (jens.nistler@media-soma.de)
*                             philipp von styp-rekowsky (philipp.styp-rekowsky@media-soma.de)
*
*/

// define debuglevel
// 3 -> verbosed debug on (with xml option in forms)
// 2 -> verbosed debug on
// 1 -> debug on
// 0 -> debug off
define('DEBUG', 2);

#################################################################################################
#################################################################################################
### DON'T CHANGE ANYTHING BELOW THIS LINE!
#################################################################################################
#################################################################################################

// define errorlevels
define('ERR_RUNTIME', 1);
define('ERR_DB', 2);
define('ERR_INPUT', 4);

// set engine to keep running on user abort
ignore_user_abort(true);

#################################################################################################
### basic error handling
#################################################################################################

// set php-errorhandling
if (DEBUG > 0)
{
	ini_set('error_reporting', E_ALL);
	ini_set('display_errors', 1);
}
else
	ini_set('display_errors', 0);

#################################################################################################
### unibox exception handling
#################################################################################################

abstract class ub_exception extends Exception
{
	protected $message = null;

	public function __construct($message = null)
	{
		$this->message = $message;
	}

	public function get_message()
	{
		return $this->message;
	}

	abstract public function process();

	protected function print_error($type)
	{
		echo	'<html><head><style type="text/css">body, td { font-family: Verdana, Arial, Sans-Serif; font-size: 12px; }</style></head><body>
				 <h2>'.$type.':</h2>
				 <h3>'.$this->message.'</h3>';
    }

	protected function print_backtrace()
	{
		$backtrace_array = array_reverse($this->getTrace());
		if (count($backtrace_array) > 0)
		{
			$backtrace = 	'<h2>Backtrace:</h2>
							 <table><tr><td colspan="2"><hr/></td></tr>';

			// reverse backtrace and loop through it
			foreach ($backtrace_array as $key => $step)
			{
				if (isset($step['file']))
					$backtrace .= '<tr><td width="100"><strong>File:</strong></td><td>'.$step['file'].'</td></tr>';
				if (isset($step['line']))
					$backtrace .= '<tr><td><strong>Line:</strong></td><td>'.$step['line'].'</td></tr>';
				if (isset($step['class']))
					$backtrace .= '<tr><td><strong>Class:</strong></td><td>'.$step['class'].'</td></tr>';
				if (isset($step['function']))
					$backtrace .= '<tr><td><strong>Function:</strong></td><td>'.$step['function'].'</td></tr>';
				$backtrace .= '<tr><td colspan="2"><hr/></td></tr>';
			}
			$backtrace .= '</table>';
			echo $backtrace;
		}
	}

	protected function print_context()
	{
		if (DEBUG >= 2)
		{
			ob_start();
			var_dump($GLOBALS);
			$context = ob_get_contents();
			ob_end_clean();
			echo '<h2>Context:</h2><pre>'.$context.'</pre>';
		}
	}
}

class ub_exception_general extends ub_exception
{
    protected $message_user = null;
    protected $trl_args = array();
    
    public function __construct($message = null, $message_user = null, $trl_args = array())
    {
        parent::__construct($message);
        $this->message_user = $message_user;
        $this->trl_args = $trl_args;
    }
    
    public function process($message = null, $trl_args = array())
    {
        // get unibox object
        $unibox = ub_unibox::get_instance();

        // log error
        $unibox->log(LOG_ERR_GENERAL, $this->message);

        // write message
        $msg = new ub_message(MSG_ERROR);
        if ($this->message_user !== null)
            $msg->add_text($this->message_user, $this->trl_args);
        elseif ($message != null)
            $msg->add_text($message, $trl_args);
        else
        	$msg->add_text('TRL_ERR_GENERAL_ERROR');
        $msg->display();
    }
}

class ub_exception_runtime extends ub_exception
{
    public function __construct($message = null)
    {
        parent::__construct($message);
    }
    
    public function process()
    {
        if (DEBUG > 0)
        {
            // get unibox object
            $unibox = ub_unibox::get_instance();
    
            // build errormessage
            $this->print_error('Runtime Error');

            // log error
            $unibox->log(LOG_ERR_RUNTIME, $this->message);

            // print backtrace
            $this->print_backtrace();

            // add context if debuglevel is (at least) verbosed
            $this->print_context();

            // display error and die
            echo '</body></html>';
        }
        elseif (file_exists('error.html'))
            readfile('error.html');
        else
        	echo 'Runtime Error: '.$this->message;

        die();
    }
}

class ub_exception_database extends ub_exception
{
    public function __construct($message = null)
    {
        parent::__construct($message);
    }
    
    public function process()
    {
        if (DEBUG > 0)
        {
            // get unibox object
            $unibox = ub_unibox::get_instance();
            
            // build errormessage
            $this->print_error('Database Error');
            
            // build log array
            $log_array = array($this->message);

            $db_error = $unibox->db->get_error_message();
            if (!empty($db_error))
            {
                $log_array[] = $db_error;
                echo '<h2>DB Message:</h2><h3>'.$db_error.'</h3>';

                $last_query = $unibox->db->get_last_query();
                if (!empty($last_query))
                {
                    $log_array[] = $last_query;
                    echo '<h2>Last Query:</h2><h3>'.$last_query.'</h3>';
                }
            }
    
            // log error
            $unibox->log(LOG_ERR_DB, $log_array);
    
            // print backtrace
            $this->print_backtrace();
    
            // add context if debuglevel is (at least) verbosed
            $this->print_context();
    
            // display error and die
            echo '</body></html>';
        }
        elseif (file_exists('error.html'))
            readfile('error.html');
        else
        	echo 'Database Error: '.$this->message;

        die();
    }
}

class ub_exception_security extends ub_exception
{
    protected $message_user = null;
    protected $trl_args = array();
    
    public function __construct($message = null, $message_user = null, $trl_args = array())
    {
        parent::__construct($message);
        $this->message_user = $message_user;
        $this->trl_args = $trl_args;
    }
    
    public function process($message = null, $trl_args = array())
    {
        // get unibox object
        $unibox = ub_unibox::get_instance();

        // log error
        $unibox->log(LOG_ERR_SECURITY, $this->message);

        // display error
        if ($this->message_user !== null || $message !== null)
        {
            $msg = new ub_message(MSG_ERROR, false);
            if ($this->message_user !== null)
                $msg->add_text($this->message_user, $this->trl_args);
            else
                $msg->add_text($message, $trl_args);
            $msg->display();
        }
    }
}

class ub_exception_transaction extends ub_exception
{
    protected $message_user = null;
    protected $trl_args = array();
    
    public function __construct($message = null, $message_user = null, $trl_args = array())
    {
        parent::__construct($message);
        $this->message_user = $message_user;
        $this->trl_args = $trl_args;
    }
    
    public function process($message = null, $trl_args = array())
    {
        // get unibox object
        $unibox = ub_unibox::get_instance();

        // rollback transaction
        if ($this->message_user !== null)
            $unibox->db->rollback($this->message_user, $this->trl_args);
        elseif ($message !== null)
            $unibox->db->rollback($message, $trl_args);
		else
			$unibox->db->rollback('TRL_GENERAL_TRANSACTION_ERROR');

        // build log array
        $log_array = array($this->message);

        $db_error = $unibox->db->get_error_message();
        if (!empty($db_error))
        {
            $log_array[] = $db_error;

            $last_query = $unibox->db->get_last_query();
            if (!empty($last_query))
                $log_array[] = $last_query;
        }

        // log error
        $unibox->log(LOG_ERR_DB, $log_array);
    }
}

#################################################################################################
### autoload function
#################################################################################################

function get_framework_classes()
{
	return array(	'ub_administration' => array(
						DIR_FRAMEWORK.'administration.php'),
					'ub_administration_ng' => array(
						DIR_FRAMEWORK.'administration_ng.php'),
					'ub_archive' => array(
						DIR_FRAMEWORK.'archive.php'),
					'ub_config' => array(
						DIR_FRAMEWORK.'config.php'),
					'ub_db' => array(
						DIR_FRAMEWORK_DATABASE.'database.php',
						DIR_FRAMEWORK_DATABASE.'common.php'),
					'ub_db_layer_mysql' => array(
						DIR_FRAMEWORK_DATABASE_LAYERS.'mysql.php'),
					'ub_db_layer_mysqli' => array(
						DIR_FRAMEWORK_DATABASE_LAYERS.'mysqli.php'),
					'ub_db_tools_mysql' => array(
						DIR_FRAMEWORK_DATABASE_TOOLS.'mysql.php'),
					'ub_dialog' => array(
						DIR_FRAMEWORK.'dialog.php'),
					'ub_email' => array(
						DIR_FRAMEWORK.'email.php'),
					'ub_form_creator' => array(
						DIR_FRAMEWORK.'form_creator.php'),
					'ub_functions' => array(
						DIR_FRAMEWORK.'functions.php'),
					'ub_message' => array(
						DIR_FRAMEWORK.'message.php'),
					'ub_pagebrowser' => array(
						DIR_FRAMEWORK.'pagebrowser.php'),
					'ub_preselect' => array(
						DIR_FRAMEWORK.'preselect.php'),
					'ub_search' => array(
						DIR_FRAMEWORK.'search.php'),
					'ub_session' => array(
						DIR_FRAMEWORK.'session.php'),
					'ub_sql_builder' => array(
						DIR_FRAMEWORK.'sql_builder.php'),
					'ub_stack' => array(
						DIR_FRAMEWORK.'stack.php'),
					'ub_tar' => array(
						DIR_FRAMEWORK.'tar.php'),
					'ub_time' => array(
						DIR_FRAMEWORK.'time.php'),
					'ub_ubc' => array(
						DIR_FRAMEWORK.'ubc.php'),
					'ub_ucm' => array(
						DIR_FRAMEWORK.'ucm.php'),
					'ub_unibox' => array(
						DIR_FRAMEWORK.'unibox.php'),
					'ub_update' => array(
						DIR_FRAMEWORK.'update.php'),
					'ub_validator' => array(
						DIR_FRAMEWORK.'validator.php'),
					'ub_xml' => array(
						DIR_FRAMEWORK.'xml.php'),
					'ub_xsl' => array(
						DIR_FRAMEWORK.'xsl.php'));
}

/**
 * includes module file if a member function is called
 * 
 * @param	      $classname          classname to be processed (string)
 */
function __autoload($classname)
{
	$framework_classes = get_framework_classes();

	if (array_key_exists($classname, $framework_classes))
		foreach ($framework_classes[$classname] as $filename)
		{
			if (!is_readable($filename))
				die('the file containing the class \''.$classname.'\' could not be found/opened'); 
			include($filename);
		}
	else
	{
		// get framework and db instance
		$unibox = ub_unibox::get_instance();

		$sql_string  = 'SELECT
						  class_filename,
						  module_ident
						FROM
						  sys_class_files
						WHERE
						  class_name = \''.$classname.'\'';
		$result = $unibox->db->query($sql_string, 'getting the filename for class \''.$classname.'\' failed');
		if ($result->num_rows() == 1)
		{
			list($class_filename, $module_ident) = $result->fetch_row();

			if (!is_readable(DIR_MODULES.$module_ident.'/'.$class_filename.'.php'))
				die('the file containing the class \''.$classname.'\' could not be found/opened'); 
			include(DIR_MODULES.$module_ident.'/'.$class_filename.'.php');
		}
		else
			die('no file found for classname: \''.$classname.'\'');
	}
} // end __autoload()

#################################################################################################
### define extended standard class for environment implementation
#################################################################################################

class extStdClass extends stdClass
{
	protected $sleepcode = ';';
	protected $wakeupcode = ';';
	
	public function set_sleep($code)
	{
		$this->sleepcode = $code;
	}

	public function set_wakeup($code)
	{
		$this->wakeupcode = $code;
	}

	public function __sleep()
	{
		eval($this->sleepcode);
		return array_keys(get_object_vars($this));
	}

	public function __wakeup()
	{
		eval($this->wakeupcode);
	}
}

#################################################################################################
### framework initialization
#################################################################################################

// try loading constants
if (!is_readable('constants.php'))
	die('the file containing the constants could not be found');
include('constants.php');

$unibox = ub_unibox::get_instance();
$unibox->init();

?>