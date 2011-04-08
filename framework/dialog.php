<?php

/**
*
* uniBox 2.0 (winterspring)\n
* (c) Media Soma GbR
*
* 0.1       01.09.2006  pr      1st release\n
*
*/

class ub_dialog
{
    /**
     * class version
     */
    const version = '0.1.0';

	/**
	 * array of class instances
	 */
	private static $instances = array();

	/**
	 * unibox framework instance
	 */
	protected $unibox;
    
    protected $ident;
    protected $dialog;
    protected $steps = array();
    protected $show_disabled = array();
    protected $valid_step = 0;
    protected $conditions = array();
    protected $current_condition;
    
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
        return ub_dialog::version;
    } // end get_version()
	
    /**
    * class constructor
    * 
    * @param        $ident              dialog ident
    */
    protected function __construct($ident)
    {
        $this->unibox = ub_unibox::get_instance();
        $this->ident = $ident;

        if (!isset($this->unibox->session->env->dialog->$ident))
        	$this->init_dialog($ident);

        $this->dialog = &$this->unibox->session->env->dialog->$ident;
    } // end __construct()

	protected function init_dialog($ident)
	{
    	$this->unibox->session->env->dialog->$ident = new stdClass();
    	$this->unibox->session->env->dialog->$ident->ident = $ident;
    	$this->unibox->session->env->dialog->$ident->step = 1;
    	$this->unibox->session->env->dialog->$ident->max_step = 1;
    	$this->unibox->session->env->dialog->$ident->history = array();
    	$this->unibox->session->env->dialog->$ident->forms = array();
    	$this->unibox->session->env->dialog->$ident->disabled = array();
	}

    /**
    * returns class instance
    * 
    * @return       ub_dialog (object)
    */
    public static function get_instance($ident = null)
    {
        $unibox = ub_unibox::get_instance();

        if ($ident === null)
            $ident = $unibox->session->env->system->action_ident;

        if (!isset(self::$instances[$ident]))
            self::$instances[$ident] = new ub_dialog($ident);

        return self::$instances[$ident];
    }
    
    public static function instance_exists($ident = null)
    {
		$unibox = ub_unibox::get_instance();

        if ($ident === null)
            $ident = $unibox->session->env->system->action_ident;

    	return isset(self::$instances[$ident]);
    }
    
    /**
     * init()
     * 
     * dialog handler - handles all dialog-based actions
     * 
     * @access  public
     */
    public function init()
    {
        // get dialog information
        $sql_string  = 'SELECT
                          a.step,
						  a.show_disabled,
                          b.string_value
                        FROM
                          sys_dialogs AS a
                            LEFT JOIN sys_translations AS b
                              ON
                                (b.string_ident = a.si_step_descr
                                AND
                                b.lang_ident = \''.$this->unibox->session->lang_ident.'\')
                        WHERE
                          a.action_ident = \''.$this->ident.'\'
                        ORDER BY
                          a.step';
        $result = $this->unibox->db->query($sql_string, 'failed to retrieve dialog information');

		// check if a dialog is definded
        if ($result->num_rows() == 0)
            throw new ub_exception_runtime('no dialog defined for '.$this->ident);

		// save step information
        while (list($step_no, $show_disabled, $step_descr) = $result->fetch_row())
        {
            $this->steps[$step_no] = $step_descr;
            $this->show_disabled[$step_no] = $show_disabled;
        }
        $result->free();

		// load dialog template
        $this->unibox->load_template('shared_dialog_handler');

        // process dialog
        if (!$this->process())
        	return;

        // draw dialog
        $this->draw();

        // append step to location info
        $this->unibox->xml->goto('location');
        $this->unibox->xml->add_node('component_step');
        $this->unibox->xml->add_value('step_no', $this->dialog->step);
        $this->unibox->xml->add_value('step_descr', $this->steps[$this->dialog->step]);
        $this->unibox->xml->parse_node();
        $this->unibox->xml->restore();

        return $this->dialog->step;
    } // end init()

    protected function process()
    {
        $validator = ub_validator::get_instance();

        // check if a form was sent
        if ($validator->form_sent())
        {
            // check if submitted form is part of the dialog
            $form_index = array_search($validator->get_form_name(), $this->dialog->forms);
            if (($form_index !== false && $form_index == $this->dialog->step))
            {
            	// if so, validate the submitted form
                if ($validator->form_validate())
                {
                	// if the form is valid, mark the step as finished and advance to the next step
                    $this->dialog->history[$this->dialog->step] = true;

					// reset all steps that depend on the just submitted one
					$sql_string =  'SELECT
									  step
									FROM
									  sys_dialog_dependencies
									WHERE
									  action_ident = \''.$this->ident.'\'
									  AND
									  depends_on_step = \''.$this->dialog->step.'\'
									ORDER BY
									  step DESC';
					$result = $this->unibox->db->query($sql_string, 'failed to retrieve dialog dependecies');
					while (list($step) = $result->fetch_row())
					{
						// mark respective step as incorrect in dialog history, if it exists
						if (isset($this->dialog->history[$step]))
							$this->dialog->history[$step] = false;
						
						// reset form if it exists
						if (isset($this->dialog->forms[$step]))
							ub_form_creator::reset($this->dialog->forms[$step], true, false);
					}
					$result->free();
					
					// check if the next button was pressed
					if ($this->unibox->session->env->form->{$validator->get_form_name()}->data->submit_id == 'next')
	                    // check if current step is the maximum one, if so, increment max_step
	                    if ($this->dialog->step == $this->dialog->max_step)
	                    	$this->dialog->step = ++$this->dialog->max_step;
	                    else
		                    $this->dialog->step++;
                }
                else
                    // if the form is invalid, mark the step as incorrect
                    $this->dialog->history[$this->dialog->step] = false;
            }
        }
        else
        {
            // check if dialog was reset
            if (!isset(self::$instances[$this->ident]))
                return false;
        }

		// sort dialog history by keys
		ksort($this->dialog->history);
		
		// loop through all finished steps that are not disabled and evaluate conditions
		foreach (array_keys(array_filter($this->dialog->history, create_function('$value', 'return $value;'))) as $step)
		{
	    	// skip step if its disabled
	    	if (in_array($step, $this->dialog->disabled))
	    		continue;
	    		
	        // check if there are any conditions for this step
	        if (isset($this->conditions[$step]))
	        {
	        	// loop through all conditions
	        	foreach ($this->conditions[$step] as $condition)
	        	{
	        		// check if a value is set
	        		if (isset($this->unibox->session->env->form->{$this->dialog->forms[$step]}->data->{$condition->ident}))
	        			$value = $this->unibox->session->env->form->{$this->dialog->forms[$step]}->data->{$condition->ident};
	        		else
	        			continue;
	        			
	        		// switch condition type
	        		switch ($condition->type)
	        		{
	        			case CHECK_EQUAL:
	                		if ($value != $condition->args)
	                			continue 2;
	                		break;
	
	                	case CHECK_UNEQUAL:
	                		if ($value == $condition->args)
	                			continue 2;
	                		break;
	                		
						default:
							$validator = ub_validator::get_instance();
							
							// check if form spec exists, if not, use default value type 'string'
							if (@isset($this->unibox->session->env->form->{$this->dialog->forms[$step]}->spec->elements->{$condition->ident}->value_type))
								$value_type = $this->unibox->session->env->form->{$this->dialog->forms[$step]}->spec->elements->{$condition->ident}->value_type;
							else
								$value_type = TYPE_STRING;

							// evaluate condition
							if (!$validator->validate('VALUE', $value, $value_type, $condition->type, null, $condition->args))
								continue 2;
							break;
	        		}
	
	    			// execute the respective action
	    			switch ($condition->action)
	    			{
	    				case DIALOG_STEPS_DISABLE:
							$this->disable_step($condition->steps);
	    					break;
	    				
	    				case DIALOG_STEPS_ENABLE:
							$this->enable_step($condition->steps);
	    					break;
	    			}
	        	}
	        }
		}

		// check if the current step is disabled, if so, skip it
		while (in_array($this->dialog->step, $this->dialog->disabled))
			$this->dialog->step++;

		// check if skipping steps lead to a new maximum step
		if ($this->dialog->step > $this->dialog->max_step)
			$this->dialog->max_step = $this->dialog->step;

		// determine the maximum valid step
		$this->valid_step = array_search(false, ub_functions::array_intersect_key($this->dialog->history, array_flip(array_diff(array_keys($this->dialog->history), $this->dialog->disabled))), true);
		if ($this->valid_step === false)
			$this->valid_step = $this->dialog->max_step;
			
		// check if reenabling steps lead to an invalid valid_step
		if ($this->valid_step > $this->dialog->max_step)
			$this->valid_step = $this->dialog->max_step;
		
		// check if the user selected a specific step by clicking the respective link
        if ($validator->validate('GET', 'step', TYPE_INTEGER, CHECK_INSET, null, array_diff(range(1, $this->dialog->max_step), $this->dialog->disabled)))
            $this->dialog->step = $this->unibox->session->env->input->step;

		// check if the current step is valid
		if ($this->dialog->step > $this->valid_step)
		{
			$msg = new ub_message(MSG_ERROR, false);
			$msg->add_text('TRL_ERR_MUST_FIRST_FINISH_CURRENT_STEP');
			$msg->display();
			
			$this->dialog->step = $this->valid_step;
		}
		
		// check if the current step has already been displayed, if so, restore the form
        if (isset($this->dialog->forms[$this->dialog->step]))
            $validator->set_restore(true, $this->dialog->forms[$this->dialog->step]);

        return true;
    }

	public function register_form($name, $step = null)
	{
		if ($step === null)
			$step = $this->dialog->step;

		if (!isset($this->dialog->forms[$step]))
			$this->dialog->forms[$step] = $name;
	}
	
	public function get_forms()
	{
		return $this->dialog->forms;
	}
	
	public function reset()
	{
        // jump to dialog node and remove it
        $this->unibox->xml->goto('dialog');
        $this->unibox->xml->remove();

        // add new steps node
    	$this->unibox->xml->add_node('dialog');
        $this->unibox->xml->set_marker('dialog');

        // close node and restore previous node
        $this->unibox->xml->parse_node();
        $this->unibox->xml->restore();

		// unset dialog instance
		$this->dialog->step = 1;
        unset($this->unibox->session->env->dialog->{$this->ident});
        unset(self::$instances[$this->ident]);
	}
	
	public function disable_step($steps)
	{
		if (!is_array($steps))
			$steps = array($steps);
		
		foreach ($steps as $step)
			if (!in_array($step, $this->dialog->disabled))
				$this->dialog->disabled[] = $step;
	}
	
	public function enable_step($steps)
	{
		if (!is_array($steps))
			$steps = array($steps);
		
		foreach ($steps as $step)
		{
			if (($index = array_search($step, $this->dialog->disabled)) !== false)
			{
				unset($this->dialog->disabled[$index]);
				
				// adjust max_step if previous steps are now enabled
				if ($step < $this->dialog->max_step && !isset($this->dialog->history[$step]))
					$this->dialog->max_step = $step;
			}
		}
	}
	
	public function finish_step($step, $form_name)
	{
		$this->dialog->history[$step] = true;
		$this->register_form($form_name, $step);
		
		// adjust max step
		if ($step > $this->dialog->max_step)
			$this->dialog->max_step = $step;
	}
	
	public function set_step($step, $redraw = true)
	{
		$this->dialog->step = $step;
	
		// redraw dialog if needed
		if ($redraw)
			$this->redraw();
	}
	
	public function set_condition($step, $ident, $type, $args, $action, $steps)
	{
		$condition = new stdClass;
		$condition->ident = $ident;
		$condition->type = $type;
		$condition->args = $args;
		$condition->action = $action;
		$condition->steps = $steps;
		$this->conditions[$step][] = $condition;
	}

    public function redraw()
    {
        // process dialog...
        if (!$this->process())
        	return;

        // redraw dialog
        $this->draw();
    }

    protected function draw()
    {
    	// jump to steps node
    	$this->unibox->xml->goto('dialog');

		// clear already drawn steps
        $this->unibox->xml->remove();

        // add new steps node
    	$this->unibox->xml->add_node('dialog');
        $this->unibox->xml->set_marker('dialog');

        $this->unibox->xml->add_value('link_url', $this->unibox->session->env->alias->name);

        foreach ($this->steps as $step_no => $step_descr)
        {
        	if (!in_array($step_no, $this->dialog->disabled) || $this->show_disabled[$step_no] == 1)
        	{
	            $this->unibox->xml->add_node('step');
	            $this->unibox->xml->add_value('number', $step_no);
	            $this->unibox->xml->add_value('descr', $step_descr);

	            if ($this->show_disabled[$step_no] == 1 && in_array($step_no, $this->dialog->disabled))
	            {
	            	$this->unibox->xml->add_value('status', 'disabled');
	            	$this->unibox->xml->add_value('linked', 0);
	            }
	            elseif ($step_no == $this->dialog->step)
	            {
	                $this->unibox->xml->add_value('status', 'active');
	                $this->unibox->xml->add_value('linked', 0);
	            }
	            elseif (isset($this->dialog->history[$step_no]) && $this->dialog->history[$step_no])
	            {
	                $this->unibox->xml->add_value('status', 'finished');
	                $this->unibox->xml->add_value('linked', 1);
	            }
	            elseif (isset($this->dialog->history[$step_no]) && !$this->dialog->history[$step_no])
	            {
	                $this->unibox->xml->add_value('status', 'incorrect');
	                $this->unibox->xml->add_value('linked', $step_no <= $this->dialog->max_step);
	            }
	            elseif ($step_no == $this->dialog->max_step)
	            {
	                $this->unibox->xml->add_value('status', 'current');
	                $this->unibox->xml->add_value('linked', 1);
	            }
	            else
	            {
	                $this->unibox->xml->add_value('status', 'todo');
	                $this->unibox->xml->add_value('linked', 0);
	            }
	            $this->unibox->xml->parse_node();
        	}
        }

		// close dialog node
		$this->unibox->xml->parse_node();

		// restore previous node
        $this->unibox->xml->restore();
    }
}

?>