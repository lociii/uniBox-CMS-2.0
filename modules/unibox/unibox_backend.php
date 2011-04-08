<?php

/**
*
* uniBox 2.0 (winterspring)
* (c) Media Soma GbR
*
*/

class ub_unibox_backend
{
    /**
    * $version
    *
    * variable that contains the class version
    * 
    * @access   protected
    */
    const version = '0.1.1';

    /**
    * $instance
    *
    * instance of own class
    * 
    * @access   protected
    */
    private static $instance = NULL;

    /**
    * $unibox
    *
    * complete unibox framework
    * 
    * @access   protected
    */
    protected $unibox = NULL;

    /**
    * get_version()
    *
    * returns class version
    * 
    * @access   public
    * @return   float       version-number
    */
    public static function get_version()
    {
        return ub_unibox_backend::version;
    }

    /**
    * get_instance()
    *
    * return class instance
    * 
    * @access   public
    * @return   object      object of current class
    */
    public function get_instance()
    {
        if (self::$instance === null)
            self::$instance = new ub_unibox_backend;
        return self::$instance;
    }

    /**
    * __construct()
    *
    * session constructor - gets called everytime the objects get instantiated
    * 
    * @access   public
    */
    protected function __construct()
    {
        $this->unibox = ub_unibox::get_instance();
    }

	public function clear_cache()
	{
		
	}
}

?>