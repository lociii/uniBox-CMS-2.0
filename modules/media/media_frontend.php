<?php

/**
*
* uniBox 2.0 (winterspring)
* (c) Media Soma GbR
*
*/

class ub_media_frontend
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
    * singleton class instance
    * 
    */
    protected static $instance = NULL;

    /**
    * returns class version
    * 
    * @return       version-number (float)
    */
    public static function get_version()
    {
        return ub_media_frontend::version;
    } // end get_version()

    /**
    * class constructor
    *
    */
    private function __construct()
    {
        $this->unibox = ub_unibox::get_instance();
        $this->unibox->config->load_config('media');
    } // end __construct()

    /**
    * returns class instance
    * 
    * @return       object of current class
    */
    public static function get_instance()
    {
        if (self::$instance === NULL)
            self::$instance = new ub_media_frontend;
        return self::$instance;
    } // end get_instance()

    public function validate_media()
    {
        if (count($categories = $this->unibox->session->get_allowed_categories('media_show')) > 0)
            $where = 'category_id IN ('.implode(', ', $categories).')';
        
        if (isset($this->unibox->session->access) && isset($this->unibox->session->access->media) && is_array($this->unibox->session->access->media) && count($this->unibox->session->access->media) > 0)
            if (isset($where))
                $where .= ' OR media_id IN ('.implode(', ', $this->unibox->session->access->media).')';
            else
                $where = 'media_id IN ('.implode(', ', $this->unibox->session->access->media).')';

        if (isset($where))
        {
            $validator = ub_validator::get_instance();
            $sql_string  = 'SELECT
                              media_id
                            FROM
                              data_media_base
                            WHERE
                              '.$where;
            if ($validator->validate('GET', 'media_id', TYPE_INTEGER, CHECK_INSET_SQL, null, $sql_string))
                return true;
        }
        return false;
    }

    public function description()
    {
        if ($this->validate_media())
        {
            $sql_string  = 'SELECT
                              media_descr
                            FROM
                              data_media_base_descr
                            WHERE
                              media_id = '.$this->unibox->session->env->input->media_id.'
                              AND
                              lang_ident = \''.$this->unibox->session->lang_ident.'\'';
            $result = $this->unibox->db->query($sql_string, 'failed to select themes');
            if ($result->num_rows() == 1)
            {
                list($media_descr) = $result->fetch_row();
                header('Content-Type: text/plain; Charset=UTF-8');
                header('Cache-control: no-store, no-cache, must-revalidate');
                die($media_descr);
            }
            header('HTTP/1.0 404 Not Found');
            die();
        }
        header('HTTP/1.0 403 Forbidden');
        die();
    }
}

?>