<?php

/**
*
* uniBox 2.0 (winterspring)
* (c) Media Soma GbR
*
* 0.1       28.03.2006  pr,jn      1st release
*
*/

class ub_stack
{
    /**
     * class version
     */
    const version = '0.1.0';

	/**
	 * reference to stack object in environment
	 */
	protected $stack = null;
	
	/**
	 * array of class instances
	 */
	private static $instances = array();

	/**
	 * unibox framework instance
	 */
	protected $unibox;
    
    protected $invalid = array();
    
    protected $ident = null;

    protected $has_single_element = false;

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
        return ub_stack::version;
    } // end get_version()
	
    /**
    * class constructor
    * 
    * @param        $ident              stack ident
    */
    protected function __construct($ident)
    {
        $this->unibox = ub_unibox::get_instance();
        $this->ident = $ident;

        if (!isset($this->unibox->session->env->stack->$ident))
        {
        	$this->unibox->session->env->stack->$ident = new stdClass();
        	$this->unibox->session->env->stack->$ident->ident = $ident;
            $this->unibox->session->env->stack->$ident->valid = false;
            $this->unibox->session->env->stack->$ident->administration = null;
            $this->unibox->session->env->stack->$ident->has_single_element = true;
        	$this->unibox->session->env->stack->$ident->stack = array();
        }

        $this->stack = &$this->unibox->session->env->stack->$ident;
        if ($this->count() > 1)
            $this->stack->has_single_element = false;
    } // end __construct()

    /**
    * returns class instance
    * 
    * @return       ub_stack (object)
    */
    public static function get_instance($ident = null)
    {
        $unibox = ub_unibox::get_instance();
        if ($ident === null)
            $ident = $unibox->session->env->system->action_ident;
        if (!isset($unibox->session->env->stack->$ident) || !isset(self::$instances[$ident]))
            self::$instances[$ident] = new ub_stack($ident);
        return self::$instances[$ident];
    }
    
    public static function kill_instance($ident = null)
    {
        $unibox = ub_unibox::get_instance();
        if ($ident === null)
            $ident = $unibox->session->env->system->action_ident;

        unset($unibox->session->env->stack->$ident);
        unset(self::$instances[$ident]);
    }

    public static function discard_top($ident = null)
    {
        $unibox = ub_unibox::get_instance();
        if ($ident === null)
            $ident = $unibox->session->env->system->action_ident;

        if (!empty($unibox->session->env->stack->$ident->stack))
            array_pop($unibox->session->env->stack->$ident->stack);
    }

    public static function discard_all($ident = null)
    {
        $stack = ub_stack::get_instance($ident);
        $stack->clear();
        $stack->hide_status_message();
    }
    
    /**
     * pushes data onto the stack
     * 
     * @param		$data			data to push onto stack
     */
    public function push($data)
    {
        if (($key = array_search($data, $this->stack->stack)) !== false)
            unset($this->stack->stack[$key]);
        else
        {
            if (!$this->is_empty())
                $this->stack->has_single_element = false;
            $this->stack->valid = false;
        }
		array_push($this->stack->stack, $data);
    } // end push()
    
    public function unshift($data)
    {
        if (($key = array_search($data, $this->stack->stack)) !== false)
            unset($this->stack->stack[$key]);
        else
        {
            if (!$this->is_empty())
                $this->stack->has_single_element = false;
            $this->stack->valid = false;
        }
        array_unshift($this->stack->stack, $data);
    }
            
    public function pop()
    {
        $return = new stdClass();
        if (!empty($this->stack->stack))
        {
            $top = array_pop($this->stack->stack);
            foreach ($top as $key => $value)
                $return->$key = $value;
            return $return;
        }
        return false;
    } // end pop()

	public function top()
	{
		$return = new stdClass();
        if (!empty($this->stack->stack))
        {
            $top = $this->stack->stack[$this->count() - 1];
            foreach ($top as $key => $value)
                $return->$key = $value;
            return $return;
        }
		return false;
	} // end top()

	public function is_empty()
	{
		return empty($this->stack->stack);
	}
    
    public function keep_keys($keys = array())
    {
        $current = &$this->stack->stack[key($this->stack->stack)];
        // workaround for array_insertsect_key (PHP >= 5.1.0RC1)
        if (is_array($current))
            foreach ($current as $key => $value)
                if (!in_array($key, $keys))
                    unset($current[$key]);
    }
    
    public function element_invalid()
    {
        $this->invalid[] = $this->current();
        $this->stack->stack[key($this->stack->stack)] = null;
    }
    
    public function is_valid()
    {
        return $this->stack->valid;
    }
	
    public function set_valid($valid = true)
    {
        if ($this->stack->administration !== null && isset($this->unibox->session->env->administration->{$this->stack->administration}))
            if (!$valid)
            {
                $this->unibox->session->env->administration->{$this->stack->administration}->invalid = $this->invalid;
                $this->unibox->session->env->administration->{$this->stack->administration}->valid = $this->stack->stack;
            }
            else
            {
                $this->unibox->session->env->administration->{$this->stack->administration}->invalid = array();
                $this->unibox->session->env->administration->{$this->stack->administration}->valid = array();
            }
        $this->stack->valid = $valid;
    }
    
    public function validate()
    {
        foreach ($this->stack->stack as $key => $value)
            if ($value === null)
                unset($this->stack->stack[$key]);
        
        if (empty($this->invalid))
            $this->set_valid(true);
        else
            $this->set_valid(false);
    }
    
    public function get_stack($key = null)
    {
        if ($key === null)
            return $this->stack->stack;
        else
        {
        	$return = array();
            foreach ($this->stack->stack as $element)
                if (isset($element[$key]))
                    $return[] = $element[$key];
            return $return;
        }
    }
    
    public function set_stack($stack)
    {
        $this->stack->stack = $stack;
    }

    public function reset()
    {
        return reset($this->stack->stack);
    }
    
    public function next()
    {
        return next($this->stack->stack);
    }
    
    public function current()
    {
        return current($this->stack->stack);
    }
    
    public function prev()
    {
        return prev($this->stack->stack);
    }
    
    public function end()
    {
        return end($this->stack->stack);
    }
    
    public function count()
    {
        return count($this->stack->stack);
    }
    
    public function clear()
    {
        $this->stack->stack = array();
    }
    
	public function kill()
	{
		$this->stack->stack = array();
        $this->stack->has_single_element = true;
        $this->invalid = array();
        $this->stack->valid = false;
	}
    
    public function get_administration()
    {
        return $this->stack->administration;
    }
    
    public function set_administration($alias)
    {
        $this->stack->administration = $alias;
    }

    public function switch_to_administration($show_errors = true)
    {
        $this->kill();
        
        if ($show_errors || $this->stack->administration === null)
            $this->unibox->display_error();
            
        if ($this->stack->administration !== null)
            $this->unibox->switch_alias($this->stack->administration, true);
    }
    
    public function has_single_element()
    {
        return $this->stack->has_single_element;
    }
    
    public function hide_status_message()
    {
        $this->stack->has_single_element = true;
    }
} // end class ub_stack()

?>
