<?php

/**
*
* uniBox 2.0 (winterspring)\n
* (c) Media Soma GbR
*
* 0.1       10.06.2005  jn      1st release\n
* 0.11      14.07.2005  jn      added comments\n
* 0.2       26.06.2006  jn      administrations without 'notempty fields' are now directly shown 
* 0.21      07.07.2006  jn pr   fields can now be database independent
* 0.22		14.07.2006	pr		fixed bug
* 0.23		14.07.2006	pr		required selects with only one value will cause the preselect to be submitted directly
*
*/

class ub_preselect
{
    /**
    * class version
    * 
    */
    const version = '0.1.0';

    /**
    * unibox framework object
    * 
    */
    protected $unibox;

    /**
    * class instance
    */
    private static $instances = array();

    /**
    * preselect object ident
    * 
    */
    protected $ident = null;

    /**
    * reference to session preselect object
    * 
    */
    protected $preselect = null;

    /**
    * returns class version
    * 
    * @return       version-number (float)
    */
    public static function get_version()
    {
        return ub_preselect::version;
    }

    /**
    * initialize preselect
    * 
    * @param        $ident          preselect ident (string)
    */
    public function init($ident, $reset = false)
    {
        $unibox = ub_unibox::get_instance();
        if (!isset($unibox->session->env->preselect->$ident) || $reset)
        {
            $unibox->session->env->preselect->$ident = new StdClass();
            $unibox->session->env->preselect->$ident->ident = $ident;
            $unibox->session->env->preselect->$ident->fields = array();
        }
    }

    /**
    * class constructor - loads framework configuration
    *
    * @param        $ident          preselect ident (string)
    */
    public function __construct($ident)
    {
        $this->unibox = ub_unibox::get_instance();
        $this->ident = $ident;
        
        if (!isset($this->unibox->session->env->preselect->$ident))
        {
            $this->unibox->session->env->preselect->$ident = new extStdClass();
            $this->unibox->session->env->preselect->$ident->set_sleep('unset($this->form);');
            $this->unibox->session->env->preselect->$ident->set_wakeup('$unibox = ub_unibox::get_instance(); $this->form = &$unibox->session->env->form->{$this->ident};');
            $this->unibox->session->env->preselect->$ident->ident = $ident;
            $this->unibox->session->env->preselect->$ident->fields = array();
        }
        $this->preselect = &$this->unibox->session->env->preselect->$ident;
        $this->preselect->form = &$this->unibox->session->env->form->$ident;
    }

    /**
    * returns class instance
    * 
    * @return       ub_preselect (object)
    */
    public static function get_instance($ident)
    {
        $unibox = ub_unibox::get_instance();
        if (!isset($unibox->session->env->preselect->$ident) || !isset(self::$instances[$ident]))
            self::$instances[$ident] = new ub_preselect($ident);
        return self::$instances[$ident];
    }

    /**
    * add a field to the preselection
    * 
    * @param        $ident                  field ident (string)
    * @param        $field                  field database name - null if it shouldn't be used for query limitation (string)
    * @param        $accurate               find only accurate values (bool)
    */
    public function add_field($ident, $field = null, $accurate = false)
    {
        $this->preselect->fields[$ident]->field = $field;
        $this->preselect->fields[$ident]->accurate = $accurate;
        $this->preselect->fields[$ident]->include_db = ($field !== null);
    }

    public function set_value($ident, $value, $field = null, $accurate = false)
    {
        $this->preselect->fields[$ident]->value = $value;
    }

    public function check()
    {
        $validator = ub_validator::get_instance();
        
        if ($validator->form_sent($this->preselect->ident))
        {
            if ($this->preselect->form->data->submit_id == 0 && $validator->form_validate($this->preselect->ident))
            {
                foreach ($this->preselect->fields as $ident => $field)
                    if (isset($this->preselect->form->data->$ident))
                        $this->preselect->fields[$ident]->value = $this->preselect->form->data->$ident;
                $this->preselect->form->spec->restore = true;
            }
            else
            {
                foreach ($this->preselect->fields as $ident => $field)
                    unset($this->preselect->fields[$ident]->value);
            }
        }
        else
        {
            foreach ($this->preselect->fields as $ident => $field)
                if (isset($this->preselect->fields[$ident]->value))
                {
                    $this->preselect->form->data->$ident = $this->preselect->fields[$ident]->value;
                    $this->preselect->form->spec->restore = true;
                }
        }
    }

    /**
    * process preselection	
    * 
    * @return       indicate if sth is preselected (bool)
    */
	public function process()
	{
		$validator = ub_validator::get_instance();
		if ($validator->form_sent($this->preselect->ident))
			if ($this->preselect->form->data->submit_id == 0 && $validator->get_result())
				return true;

		$return = false;
        $return_notempty = true;
		foreach ($this->preselect->fields as $ident => $field)
        {
			if (isset($this->preselect->fields[$ident]->value))
				$return = true;
			if (ub_validator::has_condition($this->preselect->form->spec->elements->$ident, CHECK_NOTEMPTY))
				if (($this->preselect->form->spec->elements->$ident->input_type == INPUT_SELECT || $this->preselect->form->spec->elements->$ident->input_type == INPUT_RADIO) && isset($this->preselect->form->spec->elements->$ident->options) && count($this->preselect->form->spec->elements->$ident->options) == 1)
                {
					$this->preselect->fields[$ident]->value = current($this->preselect->form->spec->elements->$ident->options);
                    $this->unibox->session->env->form->{$this->preselect->form->spec->name}->data->$ident = $this->preselect->fields[$ident]->value;
                }
				else
					$return_notempty = false;
        }
		return ($return || $return_notempty);
	}

    /**
    * get the value of a preselect field
    * 
    * @param        $ident                  field name (bool)
    * @return       value (mixed)
    */
    public function get_value($ident)
    {
        if (isset($this->preselect->fields[$ident]->value))
            return $this->preselect->fields[$ident]->value;
        else
            return '';
    }

    /**
    * get sql fragment
    * 
    * @return       sql fragment (string)
    */
    public function get_string()
    {
        $string = '1';
        foreach ($this->preselect->fields as $key => $class)
        {
            if ($class->include_db && isset($class->value) && trim($class->value) != '')
            {
                $string .= ' AND ';
                if ($class->accurate)
                    $string .= $class->field.' = \''.$class->value.'\'';
                else
                    $string .= $class->field.' LIKE \'%'.$class->value.'%\'';
            }
        }
        return $string;
    } 
}

?>