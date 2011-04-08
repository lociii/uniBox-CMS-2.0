<?php

/**
*
* uniBox 2.0 (winterspring)
* (c) Media Soma GbR
*
* 0.1       04.03.2005  pr      1st release
* 0.2       05.04.2005	pr		changed location of session reference, added comments
* 0.3       12.06.2005	pr		changed to form specific data storage
* 0.31      23.02.2006  jn      custom destination directory for file handling
* 0.32      28.03.2006  jn,pr   added fake global _STACK
*
*/

class ub_validator
{
    /**
     * version
     * 
     * contains the class version
     * 
     * @access   protected
     */
    const version = '0.1.0';

	/**
	 * $result
	 * 
	 * contains the result of all checks made so far
	 * 
	 * @access	protected
	 */
	protected $result = true;
	
	/**
	 * $current_form
	 * 
	 * holds a reference to the current form
	 * 
	 * @access	protected
	 */
	protected $current_form = null;
	
	/**
	 * $instance
	 * 
	 * holds the class instance
	 * 
	 * @access	protected
	 */
	protected static $instance = null;

	/**
	 * $unibox
	 * 
	 * holds the framework instance
	 * 
	 * @access	protected
	 */
	protected $unibox;

    /**
     * holds all sent forms
     */
    protected $form_sent_cache = array();

	/**
	 * holds all validated forms
	 */
	protected $form_validation_cache = array();

    /**
    * get_version()
    *
    * returns class version
    * 
    * @return   float       version-number
    * @access   public
    */
    public static function get_version()
    {
        return ub_validator::version;
    } // end get_version()

	/**
	 * __construct
	 * 
	 * class constructor method
	 * 
	 * @access	protected
	 */
	protected function __construct()
	{
		$this->unibox = ub_unibox::get_instance();
	} // end __construct()

	/**
	 * get_instance()
	 * 
	 * returns class instance
	 * 
	 * @access	public
	 */
	public function get_instance()
	{
		if (self::$instance === null)
			self::$instance = new ub_validator;
		return self::$instance;
	} // end get_instance()

	/**
	 * validate()
	 * 
	 * checks the value at the specified location
	 * 
	 * @param	string		superglobal type
	 * @param	string		variable identifier
	 * @param	string		value datatype
	 * @param	string		condition type
	 * @param	string		error message (optional, defaults to NULL)
	 * @param	mixed		validation arguments (optional, defaults to NULL)
	 * @param	string		input field type (optional, defaults to NULL)
	 * @return	bool		validation result
	 * @access	public
	 */
	public function validate($sg_type, $ident, $value_type, $check_type, $si_err_msg = null, $args = null, $input_type = null, $form = null)
	{
        $SG_GET = &$_GET;
		$SG_POST = &$_POST;
        $SG_FILES = &$_FILES;

        $sg_type = strtoupper($sg_type);
        $err_args = array();
        $is_multilang = (isset($form) && isset($this->unibox->session->env->form->$form->spec->elements->$ident) && isset($this->unibox->session->env->form->$form->spec->elements->$ident->label_multilang));

        // stack fake superglobal
        if ($sg_type == 'STACK')
        {
            $stack = ub_stack::get_instance();
            if (!$stack->is_empty())
                $SG_STACK = $stack->current();
            else
                $SG_STACK = array();
        }

		// value fake superglobal
		if ($sg_type != 'VALUE')
		{
			if (!isset(${'SG_'.$sg_type}[$ident]))
			{
		        if ($input_type == INPUT_CHECKBOX && $check_type != CHECK_NOTEMPTY)
				{
					$this->current_form->data->$ident = 0;
					return true;
				}
				elseif ($input_type == INPUT_CHECKBOX_MULTI && $check_type != CHECK_NOTEMPTY)
				{
					$this->current_form->data->$ident = array();
					return true;
				}
				else
				{
	                if (($input_type == INPUT_CHECKBOX || $input_type == INPUT_CHECKBOX_MULTI || $input_type == INPUT_RADIO) && $si_err_msg === null)
	                    $si_err_msg = 'TRL_FORM_ERR_NOTEMPTY_SELECT';

					$this->unibox->error($si_err_msg, $form, $ident);
					$this->result = false;
					return false;
				}
			}
			$value = ${'SG_'.$sg_type}[$ident];
		}
		else
			$value = $ident;

        if  (
                (
                    (
                        !is_array($value)
                        &&
                        trim($value) == ''
                    )
                    &&
                    $check_type != CHECK_EQUAL
                )
                ||
                (
                    (
                        $input_type == INPUT_CHECKBOX_MULTI
                        ||
                        (
                            $input_type === null
                            &&
                            $value_type == TYPE_ARRAY
                        )
                    )
                    &&
                    (
                        !is_array($value)
                        ||
                        empty($value)
                    )
                )
                ||
                (
                    $input_type == INPUT_FILE
                    &&
                    $value['error'] == UPLOAD_ERR_NO_FILE
                )
            )
		{
			if ($check_type == CHECK_NOTEMPTY)
			{
                if ($form !== null && $si_err_msg == '')
                {
                    switch ($this->unibox->session->env->form->$form->spec->elements->$ident->input_type)
                    {
                        case INPUT_RADIO:
                        case INPUT_SELECT:
                            $si_err_msg = 'TRL_FORM_ERR_NOTEMPTY_SELECT';
                            break;
                        case INPUT_FILE:
                            $si_err_msg = 'TRL_FORM_ERR_NOTEMPTY_UPLOAD';
                            break;
                        default:
                            $si_err_msg = ($is_multilang) ? 'TRL_FORM_ERR_NOTEMPTY_ENTER_MULTILANG' : 'TRL_FORM_ERR_NOTEMPTY_ENTER';
                            break;
                    }
                }
				$this->unibox->error($si_err_msg, $form, $ident, $value);
				$this->result = false;
				return false;
			}

			if ($input_type === null && $sg_type != 'STACK')
				$this->unibox->session->env->input->$ident = $this->unibox->db->cleanup($value, true);
			return true;
		}

		switch ($check_type)
		{
            case CHECK_TYPE:
                switch ($value_type)
                {
                    case TYPE_INTEGER:
                        if (trim($value) != '' && (string)(int)$value != $value)
                        {
                            if ($form !== null && $si_err_msg == '')
                                $si_err_msg = 'TRL_FORM_ERR_TYPE_INTEGER';
                            $this->unibox->error($si_err_msg, $form, $ident, $value);
                            $this->result = false;
                            return false;
                        }
                        break;
                        
                    case TYPE_FLOAT:
                        if (trim($value) != '' && (string)(float)$value != $value)
                        {
                            if ($form !== null && $si_err_msg == '')
                                $si_err_msg = 'TRL_FORM_ERR_TYPE_FLOAT';
                            $this->unibox->error($si_err_msg, $form, $ident, $value);
                            $this->result = false;
                            return false;
                        }
                        break;
                        
                    case TYPE_DATE:
                        $time = new ub_time(TIME_TYPE_USER, TIME_TYPE_DB);
                        if (!$time->parse_date($value))
                        {
                            if ($form !== null && $si_err_msg == '')
                                $si_err_msg = 'TRL_FORM_ERR_TYPE_DATE';
                            $this->unibox->error($si_err_msg, $form, $ident, $value);
                            $this->result = false;
                            return false;
                        }
                        break;
                        
                    case TYPE_TIME:
                        $time = new ub_time(TIME_TYPE_USER, TIME_TYPE_DB);
                        if (!$time->parse_time($value))
                        {
                            if ($form !== null && $si_err_msg == '')
                                $si_err_msg = 'TRL_FORM_ERR_TYPE_TIME';
                            $this->unibox->error($si_err_msg, $form, $ident, $value);
                            $this->result = false;
                            return false;
                        }
                        break;
                        
                    case TYPE_ARRAY:
                        if (!is_array($value))
                        {
                            if ($form !== null && $si_err_msg == '')
                                $si_err_msg = 'TRL_FORM_ERR_TYPE_ARRAY';
                            $this->unibox->error($si_err_msg, $form, $ident, $value);
                            $this->result = false;
                            return false;
                        }
                        break;
                }
                break;

			case CHECK_INRANGE:
				if ($value_type == TYPE_STRING)
				{
					if (strlen($value) < $args[0] || strlen($value) > $args[1])
					{
                        if ($form !== null && $si_err_msg == '')
                        {
                            if ($args[0] != $args[1])
                            {
                                $si_err_msg = ($is_multilang) ? 'TRL_FORM_ERR_INRANGE_STRING_MULTILANG' : 'TRL_FORM_ERR_INRANGE_STRING';
                                $err_args = array($args[0], $args[1]);
                            }
                            else
                            {
                                $si_err_msg = ($is_multilang) ? 'TRL_FORM_ERR_INRANGE_STRING_EXACT_MULTILANG' : 'TRL_FORM_ERR_INRANGE_STRING_EXACT';
                                $err_args = array($args[0]);
                            }
                        }
						$this->unibox->error($si_err_msg, $form, $ident, $value, $err_args);
						$this->result = false;
						return false;
					}
				}
				elseif ($value_type == TYPE_INTEGER || $value_type == TYPE_FLOAT)
				{
					if ($value < $args[0] || $value > $args[1])
					{
                        if ($form !== null && $si_err_msg == '')
                        {
                            $si_err_msg = 'TRL_FORM_ERR_INRANGE_NUMERIC';
                            $err_args = array($args[0], $args[1]);
                        }
						$this->unibox->error($si_err_msg, $form, $ident, $value, $err_args);
						$this->result = false;
						return false;
					}
				}
				else
                    throw new ub_exception_runtime('invalid datatype for INRANGE-check');

				break;

			case CHECK_INSET:
				if (!in_array($value, $args))
				{
                    if ($form !== null && $si_err_msg == '')
                        $si_err_msg = 'TRL_FORM_ERR_INSET';
					$this->unibox->error($si_err_msg, $form, $ident, $value);
					$this->result = false;
					return false;
				}
				break;

            case CHECK_INSET_CI:
                array_walk($args, 'strtolower');
                if (!in_array(strtolower($value), $args))
                {
                    if ($form !== null && $si_err_msg == '')
                        $si_err_msg = 'TRL_FORM_ERR_INSET';
                    $this->unibox->error($si_err_msg, $form, $ident, $value);
                    $this->result = false;
                    return false;
                }
                break;

			case CHECK_INSET_SQL:
				$result = $this->unibox->db->query(ub_validator::prepare_inset_sql($args, $value), 'failed to select set for CHECK_INSET_SQL');
				if ($result->num_rows() < 1)
				{
                    if ($form !== null && $si_err_msg == '')
                        $si_err_msg = 'TRL_FORM_ERR_INSET';
					$this->unibox->error($si_err_msg, $form, $ident, $value);
					$this->result = false;
					return false;
				}
				
				$set = $result->fetch_row(FETCHMODE_ASSOC);
                $result->free();
				break;

            case CHECK_NOTINSET:
                if (in_array($value, $args))
                {
                    if ($form !== null && $si_err_msg == '')
                        $si_err_msg = 'TRL_FORM_ERR_NOTINSET';
                    $this->unibox->error($si_err_msg, $form, $ident, $value);
                    $this->result = false;
                    return false;
                }
                break;

            case CHECK_NOTINSET_CI:
                array_walk($args, 'strtolower');
                if (in_array(strtolower($value), $args))
                {
                    if ($form !== null && $si_err_msg == '')
                        $si_err_msg = 'TRL_FORM_ERR_NOTINSET';
                    $this->unibox->error($si_err_msg, $form, $ident, $value);
                    $this->result = false;
                    return false;
                }
                break;

			case CHECK_NOTINSET_SQL:
				$result = $this->unibox->db->query(ub_validator::prepare_inset_sql($args, $value), 'failed to select set for CHECK_INSET_SQL');
				if ($result->num_rows() > 0)
				{
                    if ($form !== null && $si_err_msg == '')
                        $si_err_msg = 'TRL_FORM_ERR_INSET';
					$this->unibox->error($si_err_msg, $form, $ident, $value);
					$this->result = false;
					return false;
				}
                $result->free();
				break;

			case CHECK_PREG:
				if (!preg_match($args, $value))
				{
                    if ($form !== null && $si_err_msg == '')
                        $si_err_msg = 'TRL_FORM_ERR_PREG';
					$this->unibox->error($si_err_msg, $form, $ident, $value);
					$this->result = false;
					return false;
				}
				break;

			case CHECK_EMAIL:
				if (!preg_match('/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@(([a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*\.([a-zA-Z]{2,6}))|(\d{1,3}\.\d{1,3}\.\d{1,3}.\d{1,3}))$/', $value))
				{
                    if ($form !== null && $si_err_msg == '')
                        $si_err_msg = 'TRL_FORM_ERR_EMAIL';
					$this->unibox->error($si_err_msg, $form, $ident, $value);
					$this->result = false;
					return false;
				}
				break;

			case CHECK_FILE_EXTENSION:
				if ($value['error'] == UPLOAD_ERR_OK)
                {
                    // check mime type if finfo available
                    // TODO: implement mime checking class
                    if (class_exists('finfo', false) && ($finfo = new finfo(FILEINFO_MIME, DIR_MIME_FILE)))
                    {
                        $mime_type = strtolower($finfo->file($value['tmp_name']));
                        foreach ($args as $allowed_mime_type)
                        {
                            $allowed_mime_type = strtolower($allowed_mime_type);
                            if (stristr($mime_type, $allowed_mime_type) || stristr($mime_type, $allowed_mime_type))
                            {
                                $this->unibox->session->env->form->$form->data->{$ident}['type'] = $allowed_mime_type;
                                break(2);
                            }
                        }
                    }
                    // check file extension
                    elseif ($file_ext = ub_functions::get_file_extension($value['name']))
                    {
                        // build where statement
                        $mime_types_where = $mime_types = $mime_subtypes = array();
                        foreach ($args as $mime_combined)
                        {
                            list($mime_type, $mime_subtype) = explode('/', $mime_combined);
                            $mime_types_where[] = '(mime_type = \''.$this->unibox->db->cleanup($mime_type).'\' AND mime_subtype = \''.$this->unibox->db->cleanup(($mime_subtype == '*') ? '%' : $mime_subtype).'\')';
                            $mime_types[] = $mime_type;
                            $mime_subtypes[] = $mime_subtype;
                        }
                        $mime_types_where = implode(' OR ', $mime_types_where);
    
                        // select file extensions of known mime types
                        $sql_string  = 'SELECT
                                          mime_file_extension,
                                          mime_type,
                                          mime_subtype
                                        FROM
                                          sys_mime_types
                                        WHERE
                                          '.$mime_types_where;
                        $result = $this->unibox->db->query($sql_string, 'failed to select mime types');
                        while (list($allowed_file_ext, $mime_type, $mime_subtype) = $result->fetch_row())
                            if ($allowed_file_ext == $file_ext)
                            {
                                $this->unibox->session->env->form->$form->data->{$ident}['type'] = $mime_type.'/'.$mime_subtype;
                                break(2);
                            }
                        $result->free();
                    }

                    if ($form !== null && $si_err_msg == '')
                        $si_err_msg = 'TRL_FORM_ERR_FILE_EXTENSION';
                    $this->unibox->error($si_err_msg, $form, $ident, $value);
                    $this->result = false;
                    return false;
                }
				break;

			case CHECK_FILE_SIZE:
				if ($value['error'] == UPLOAD_ERR_INI_SIZE || $value['error'] == UPLOAD_ERR_FORM_SIZE || ($value['error'] == UPLOAD_ERR_OK && $value['size'] > $args))
				{
                    if ($form !== null && $si_err_msg == '')
                        $si_err_msg = 'TRL_FORM_ERR_FILE_SIZE';
					$this->unibox->error($si_err_msg, $form, $ident, $value);
					$this->result = false;
					return false;
				}
				break;

			case CHECK_EQUAL:
				if (isset($this->current_form->data->$args) && $value != $this->current_form->data->$args)
				{
                    if ($form !== null && $si_err_msg == '')
                    {
                        $si_err_msg = 'TRL_FORM_ERR_EQUAL';
                        $err_args = array($this->current_form->spec->elements->$args->label);
                    }
					$this->unibox->error($si_err_msg, $form, $ident, $value, $err_args);
					$this->result = false;
					return false;
				}
				break;

			case CHECK_UNEQUAL:
				if (isset($this->current_form->data->$args) && $value == $this->current_form->data->$args)
				{
                    if ($form !== null && $si_err_msg == '')
                    {
                        $si_err_msg = 'TRL_FORM_ERR_UNEQUAL';
                        $err_args = array($this->current_form->spec->elements->$args->label);
                    }
					$this->unibox->error($si_err_msg, $form, $ident, $value, $err_args);
					$this->result = false;
					return false;
				}
				break;

            case CHECK_FILE_EXISTS:
                if (!file_exists($args.$value))
                {
                    if ($form !== null && $si_err_msg == '')
                        $si_err_msg = 'TRL_FORM_ERR_FILE_EXISTS';
                    $this->unibox->error($si_err_msg, $form, $ident, $value);
                    $this->result = false;
                    return false;
                }
                break;

            case CHECK_URL:
            	$matches = array();
                if (!preg_match('~^([a-z][a-z0-9+.\-]*)://(\w+(:\w+)?@)?[a-z0-9äüö]([a-z0-9äöü.\-])*(:[0-9]+)?~ix', $value, $matches) || !in_array(strtolower($matches[1]), $args))
                {
                    if ($form !== null && $si_err_msg == '')
                        $si_err_msg = 'TRL_FORM_ERR_URL_INVALID';
                    $this->unibox->error($si_err_msg, $form, $ident, $value);
                    $this->result = false;
                    return false;
                }
                break;
		}

		if ($form === null && $sg_type != 'STACK')
			$this->unibox->session->env->input->$ident = $this->unibox->db->cleanup($value, true);

		if (isset($set))
            return $set;
        else
            return true;
	} // end validate()

	/**
	 * validate_element()
	 * 
	 * checks given form element for all conditions
	 * 
	 * @param	object		element to check
	 * @param	string		element ident
	 * @return 	bool		validation result
	 */
	protected function validate_element($element, $ident, $multi = false)
	{
		$SG_GET = &$_GET;
		$SG_POST = &$_POST;
		$SG_FILES = &$_FILES;
		
		$result = true;
		$sg_type = ($element->input_type == INPUT_FILE) ? 'FILES' : strtoupper($this->current_form->spec->method);

        if (isset($element->conditions))
    		foreach ($element->conditions as $condition)
    			$result = $this->validate($sg_type, $ident, $element->value_type, $condition->type, $condition->message, $condition->arguments, $element->input_type, $this->current_form->spec->name) && $result;

		if ($element->input_type == INPUT_FILE)
		{
            // delete old file
            if (isset($this->current_form->data) && isset($this->current_form->data->$ident))
            {
                $element_data = $this->current_form->data->$ident;
                if (isset($element_data['tmp_name']) && file_exists($element->dest_dir.DIRECTORY_SEPARATOR.$element_data['tmp_name']) && is_writable($element->dest_dir.DIRECTORY_SEPARATOR.$element_data['tmp_name']))
                    unlink($element->dest_dir.DIRECTORY_SEPARATOR.$element_data['tmp_name']);
            }

			if ($result && ${'SG_'.$sg_type}[$ident]['error'] != UPLOAD_ERR_NO_FILE)
			{
				$file = ${'SG_'.$sg_type}[$ident];

				// check if file was completely uploaded
				if ($file['error'] == UPLOAD_ERR_PARTIAL)
				{
					$this->unibox->error('TRL_ERR_FILE_UPLOAD_PARTIAL', $this->current_form->spec->name, $ident, $file, array($this->current_form->spec->elements->$ident->label));
					unset($this->current_form->data->$ident);
					return false;
				}
				
				// check if any other error occurred
				if ($file['error'] != UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name']))
				{
					$this->unibox->error('TRL_ERR_FILE_UPLOAD_UNKNOWN_ERROR', $this->current_form->spec->name, $ident, $file, array($this->current_form->spec->elements->$ident->label));
					unset($this->current_form->data->$ident);
					return false;
				}
				
                if (!file_exists($element->dest_dir) && !mkdir($element->dest_dir))
                {
                    $this->unibox->error('TRL_ERR_FILE_UPLOAD_DEST_DIR_NOT_ACCESSIBLE', $this->current_form->spec->name, $ident, $file, array($element->dest_dir));
                    unset($this->current_form->data->$ident);
                    return false;
                }
                
				if ($result)
				{
				    // generate random filename
				    $filename = md5(uniqid(mt_rand())).'.'.ub_functions::get_file_extension($file['name']);
				
					// check if a file with the generated name already exists in the upload folder
					while (file_exists($element->dest_dir.$filename))
					    $filename = md5(uniqid(mt_rand())).'.'.ub_functions::get_file_extension($file['name']);
					
					// move uploaded file to upload folder
					if (!move_uploaded_file($file['tmp_name'], $element->dest_dir.$filename))
					{
						$this->unibox->error('TRL_ERR_FILE_UPLOAD_UNKNOWN_ERROR', $this->current_form->spec->name, $ident, $file);
						unset($this->current_form->data->$ident);
						$this->result = false;
						return false;
					}

                    $file['tmp_name'] = $filename;
                    $file['tmp_path'] = $element->dest_dir;
                    if (isset($this->current_form->data->$ident) && isset($this->current_form->data->{$ident}['type']))
                        $file['type'] = $this->current_form->data->{$ident}['type'];
					$this->current_form->data->$ident = $file;
				}
			}
		}
		elseif (isset($element->label_multilang))
		{
			list($foo, $lang_ident, $ml_ident) = explode('_', $ident, 3);
			if ($result)
			{
	            $arr = &$this->current_form->data->$ml_ident;
	            $arr[$lang_ident] = $this->unibox->db->cleanup(${'SG_'.$sg_type}[$ident], true);
			}
			else
				unset($this->current_form->data->$ml_ident[$lang_ident]);
		}
		else
		{
			if ($result)
        		$this->current_form->data->$ident = $this->unibox->db->cleanup(${'SG_'.$sg_type}[$ident], true);
			else
				unset($this->current_form->data->$ident);
		}

		return $result;
	} // end validate_element()
	
	/**
	 * get_result()
	 * 
	 * returns the result of all current checks
	 * 
	 * @access	public
	 */
	public function get_result()
	{
		$result = $this->result;
		$this->result = true;
		return $result;
	} // end get_result()
	
	/**
	 * form_sent()
	 * 
	 * checks if a form was sent
	 * sets the the submit_id of the corresponding submit button
	 * redirects if the cancel button was used
	 * 
	 * @return 	bool		result
	 * @access	public
	 */
	public function form_sent($name = null, $force_validation = false)
	{
		$SG_GET = &$_GET;
		$SG_POST = &$_POST;

		// check if form result is cached
		if ($name !== null && isset($this->form_sent_cache[$name]) && !$force_validation)
			return $this->form_sent_cache[$name];

		foreach ($this->unibox->session->env->form as $foo => $form)
		{
			if ($name !== null)
				if (isset($this->unibox->session->env->form->$name))
					$form = &$this->unibox->session->env->form->$name;
				else
					break;

			// check if form is defined
			if (isset($form->spec) && isset($form->spec->submit_indices))
			{
				// try if form was canceled
				if (isset(${'SG_'.$form->spec->method}[$form->spec->name.'_cancel']))
				{
					ub_form_creator::reset($form->spec->name);
					$this->unibox->redirect($form->spec->redirect_url);

                    $this->form_sent_cache[$form->spec->name] = false;
					return false;
				}

				// try if form was submitted
				foreach ($form->spec->submit_indices as $id)
				{
					if (isset(${'SG_'.$form->spec->method}[$form->spec->name.'_submit_'.$id]))
					{
                        // check if form was sent with valid hash
                        if (!isset(${'SG_'.$form->spec->method}['form_validation_hash_'.$form->spec->name]) || ${'SG_'.$form->spec->method}['form_validation_hash_'.$form->spec->name] != $form->spec->hash)
                        {
                            $msg = new ub_message(MSG_ERROR, false);
                            $msg->add_text('TRL_ERR_FORM_INVALID_HASH_SENT');
                            $msg->display();

                            $this->form_sent_cache[$form->spec->name] = false;
                            return false;
                        }

						$this->current_form = &$form;
						$this->current_form->data->submit_id = $id;

                        $this->form_sent_cache[$form->spec->name] = true;
						return true;
					}
				}
			}
			
			if ($name !== null)
				break;
		}
		return false;
	} // end form_sent()
	
	/**
	 * form_validate()
	 * 
	 * validates the current form
	 * 
	 * @return	bool		result
	 * @access	public
	 */
	public function form_validate($name = null, $force_validation = false)
	{
        $SG_GET = &$_GET;
        $SG_POST = &$_POST;
		$result = true;

		if (($name === null && is_object($this->current_form)) || ($name !== null && $this->form_sent($name)))
		{
			// check if form is in form cache
			if (isset($this->form_validation_cache[$this->current_form->spec->name]) && !$force_validation)
				return $this->form_validation_cache[$this->current_form->spec->name];

			foreach ($this->current_form->spec->elements as $ident => $element)
			{
 				// extract multilang-fields
				if (isset($element->label_multilang))
				{
		            list($foo, $lang_ident, $ml_ident) = explode('_', $ident, 3);
					if ($this->has_condition($element, CHECK_MULTILANG))
					{
						$multilang_fields[$ml_ident][$lang_ident] = $element;
						continue;
					}
				}

                // process callbacks
                if (isset($element->callbacks))
                {
                    foreach ($element->callbacks as $callback)
                    {
                        if (isset($this->current_form->data->$ident))
                        {
                            if ($callback->arguments === CALLBACK_PLACEHOLDER)
                                $arguments = ($callback->return_type == 2) ? array(&${'SG_'.strtoupper($this->current_form->spec->method)}[$ident]) : array(${'SG_'.strtoupper($this->current_form->spec->method)}[$ident]);
                            else
                            {
                                $arguments = $callback->arguments;
                                if ($callback->return_type == 2)
                                    $arguments[array_search(CALLBACK_PLACEHOLDER, $arguments, true)] = &${'SG_'.strtoupper($this->current_form->spec->method)}[$ident];
                                else
                                    $arguments[array_search(CALLBACK_PLACEHOLDER, $arguments, true)] = ${'SG_'.strtoupper($this->current_form->spec->method)}[$ident];
                            }

                            if (isset($callback->class))
                                $function = array($callback->class, $callback->function);
                            else
                                $function = $callback->function;
                            
                            if ($callback->return_type == 1)
                                ${'SG_'.strtoupper($this->current_form->spec->method)}[$ident] = call_user_func_array($function, $arguments);
                            else
                                call_user_func_array($function, $arguments);
                        }
                    }
                }

				if (isset($element->conditions))
					$result = $this->validate_element($element, $ident) && $result;
                elseif (isset(${'SG_'.strtoupper($this->current_form->spec->method)}[$ident]))
                {
					if (isset($element->label_multilang))
					{
			            list($foo, $lang_ident, $ml_ident) = explode('_', $ident, 3);
			            $arr = &$this->current_form->data->$ml_ident;
			            $arr[$lang_ident] = $this->unibox->db->cleanup(${'SG_'.strtoupper($this->current_form->spec->method)}[$ident], true);
					}
					// set result only if a real result was selected
					elseif (!isset($this->current_form->spec->elements->$ident->show_please_select) || !$this->current_form->spec->elements->$ident->show_please_select || trim(${'SG_'.strtoupper($this->current_form->spec->method)}[$ident]) != '')
	                    $this->current_form->data->$ident = $this->unibox->db->cleanup(${'SG_'.strtoupper($this->current_form->spec->method)}[$ident], true);
					else
						unset($this->current_form->data->$ident);
                }
                elseif ($element->input_type == INPUT_CHECKBOX)
                	$this->current_form->data->$ident = 0;
                elseif ($element->input_type == INPUT_CHECKBOX_MULTI)
                	$this->current_form->data->$ident = array();

				// automatically validate selects and radios
				if (isset(${'SG_'.strtoupper($this->current_form->spec->method)}[$ident]) && ($element->input_type == INPUT_SELECT || $element->input_type == INPUT_RADIO))
					if (!in_array(${'SG_'.strtoupper($this->current_form->spec->method)}[$ident], $element->options))
					{
						$this->unibox->error('TRL_INVALID_VALUE_PASSED', $this->current_form->spec->name, $ident, ${'SG_'.strtoupper($this->current_form->spec->method)}[$ident]);
						unset($this->current_form->data->$ident);
						$result = false;
					}

				// automatically validate multi checkboxes
				if (isset(${'SG_'.strtoupper($this->current_form->spec->method)}[$ident]) && is_array(${'SG_'.strtoupper($this->current_form->spec->method)}[$ident]) && $element->input_type == INPUT_CHECKBOX_MULTI)
				{
					foreach (${'SG_'.strtoupper($this->current_form->spec->method)}[$ident] as $value)
						if (!in_array($value, $element->options))
						{
							$this->unibox->error('TRL_INVALID_VALUE_PASSED', $this->current_form->spec->name, $ident, $value);
							unset($this->current_form->data->$ident);
							$result = false;
						}
				}
			}

			// check if we got any multilanguage-elements
			if (isset($multilang_fields) && is_array($multilang_fields))
			{
				// validate multilang-fields
				foreach ($multilang_fields as $ident => $field)
				{
					foreach ($field as $lang_ident => $element)
					{
                        // process callbacks
                        if (isset($element->callbacks))
                        {
                            foreach ($element->callbacks as $callback)
                            {
                                if (isset($this->current_form->data->$ident))
                                {
                                    if ($callback->arguments === CALLBACK_PLACEHOLDER)
                                        $arguments = ($callback->return_type == 2) ? array(&${'SG_'.strtoupper($this->current_form->spec->method)}[$ident]) : array(${'SG_'.strtoupper($this->current_form->spec->method)}[$ident]);
                                    else
                                    {
                                        $arguments = $callback->arguments;
                                        if ($callback->return_type == 2)
                                            $arguments[array_search(CALLBACK_PLACEHOLDER, $arguments, true)] = &${'SG_'.strtoupper($this->current_form->spec->method)}[$ident];
                                        else
                                            $arguments[array_search(CALLBACK_PLACEHOLDER, $arguments, true)] = ${'SG_'.strtoupper($this->current_form->spec->method)}[$ident];
                                    }
                
                                    if (isset($callback->class))
                                        $function = array($callback->class, $callback->function);
                                    else
                                        $function = $callback->function;
                                    
                                    if ($callback->return_type == 1)
                                        ${'SG_'.strtoupper($this->current_form->spec->method)}[$ident] = call_user_func_array($function, $arguments);
                                    else
                                        call_user_func_array($function, $arguments);
                                }
                            }
                        }

						$field_name = 'multilang_'.$lang_ident.'_'.$ident;
						if (isset(${'SG_'.strtoupper($this->current_form->spec->method)}[$field_name]) && trim(${'SG_'.strtoupper($this->current_form->spec->method)}[$field_name]) != '')
						{
							if (!isset($multilang_result))
								$multilang_result = true;

							$multilang_result = $this->validate_element($element, $field_name) && $multilang_result;
						}
						else
						{
							// WORKAROUND
							$arr = &$this->current_form->data->$ident;
							unset($arr[$lang_ident]);
						}
					}

					$first_element = reset($field);
					if (($this->has_condition($first_element, CHECK_NOTEMPTY) && !isset($multilang_result)))
					{
						$si_err_msg = $this->get_condition($first_element, CHECK_MULTILANG)->message; 
						if ($si_err_msg == '')
							$si_err_msg = 'TRL_FORM_ERR_NOTEMPTY_ENTER_MULTILANG_ONE';
							
						$this->unibox->error($si_err_msg, $this->current_form->spec->name, $ident);
						unset($this->current_form->data->$ident);
						$result = false;
					}
					if (isset($multilang_result))
						$result = $multilang_result && $result;
						
					unset($multilang_result);
				}
			}

			if (!$result)
			{
				$this->set_restore(true);
				$this->set_failed(true);
			}
			$this->form_validation_cache[$this->current_form->spec->name] = $result;
			return $result;
		}
		else
			return false;
	} // end form_validate()

	/**
	 * has_condition()
	 * 
	 * checks if a condition has been set for given element
	 * 
	 * @param	object		element
	 * @param	int			condition type
	 * @return	bool		result
	 */
	static function has_condition($element, $type)	
	{
		if (!isset($element->conditions))
			return false;
			
		foreach ($element->conditions as $condition)
		{
			if ($condition->type == $type)
				return true;
		}
		return false;
	} // end has_condition()
	
	/**
	 * get_condition()
	 * 
	 * returns condition of requested type
	 * 
	 * @param	object		element
	 * @param	int			condition type
	 * @return	object		condition, NULL if it doesn't exist
	 */
	protected function get_condition($element, $type)
	{
		foreach ($element->conditions as $condition)
		{
			if ($condition->type == $type)
				return $condition;
		}
		return null;
	} // end get_condition()

	/**
	 * reset()
	 * 
	 * resets the result value
	 * 
	 * @access	public
	 */
	public function reset()
	{
		$this->result = true;
		$this->unibox->session->env->error = array();
	} // end reset()

    public function reset_cache($name = null)
    {
        if ($name !== null)
            unset($this->form_sent_cache[$name], $this->form_validation_cache[$name]);
        else
            $this->form_sent_cache = $this->form_validation_cache = array();
    }

	/**
	 * sets the restore flag for the current form
	 * 
	 * @access	public
	 */
	public function set_restore($flag, $name = null)
	{
		if ($name === null)
		{
			if (is_object($this->current_form))
				$this->current_form->spec->restore = $flag;
		}
		else
			if (isset($this->unibox->session->env->form->$name))
				$this->unibox->session->env->form->$name->spec->restore = $flag;
	} // end set_restore();
	
	/**
	 * sets the failed flag for the current form
	 * 
	 * @access	public
	 */
	public function set_failed($flag, $name = null)
	{
		if ($name === null)
		{
			if (is_object($this->current_form))
				$this->current_form->spec->failed = $flag;
		}
		else
			if (isset($this->unibox->session->env->form->$name))
				$this->unibox->session->env->form->$name->spec->failed = $flag;
	} // end set_failed();

    public function set_form_failed($form_name, $field_name, $err_msg = null)
    {
        $this->unibox->error($err_msg, $form_name, $field_name, $this->unibox->session->env->form->$form_name->data->$field_name);
        unset($this->form_validation_cache[$form_name]);
        $this->set_restore(true, $form_name);
        $this->set_failed(true, $form_name);
    }

	/**
	 * get_form_name()
	 * 
	 * returns the name of the current form
	 * 
	 * @access	public
	 */
	public function get_form_name()
	{
		if (is_object($this->current_form))
			return $this->current_form->spec->name;
		else
			return null;
	} // end get_form_name()
	
	protected static function prepare_inset_sql($sql_string, $field_value)
	{
		// get field name
		$matches = array();
		preg_match('/^\s*SELECT\s+(DISTINCT\s+)?([^,\s]+)/', $sql_string, $matches);
		$field_name = $matches[2];

		if (preg_match('/(WHERE\s+)(.+)(\s*)(ORDER|LIMIT|GROUP)?/', $sql_string))
			return preg_replace('/(WHERE\s+)(.+)(\s*)(ORDER|LIMIT|GROUP)?/', '\\1(\\2) AND '.$field_name.' = \''.$field_value.'\'\\3\\4', $sql_string);
		elseif (preg_match('/(ORDER|LIMIT|GROUP)/', $sql_string))
			return preg_replace('/(ORDER|LIMIT|GROUP)/', 'WHERE '.$field_name.' = \''.$field_value.'\' \\1', $sql_string);
		else
			return $sql_string.' WHERE '.$field_name.' = \''.$field_value.'\'';
	}

} // end class ub_validator()

?>