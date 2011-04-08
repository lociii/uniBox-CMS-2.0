<?php

/**
*
* uniBox 2.0 (winterspring)\n
* (c) Media Soma GbR
*
* 0.1       22.03.2006  jn,pr       extracted pagebrowser from framework\n
*                                   invented count per page\n
*                                   improved processing\n
*
*/

class ub_pagebrowser
{
    /**
    * class version
    */
    const version = '0.1.0';

    /**
    * unibox framework object
    */
    protected $unibox;

    /**
    * pagebrowser ident
    */
    protected $ident = null;

    /**
     * array of class instances
     */
    private static $instances = array();

    /**
     * reference to pagebrowser object in environment
     */
    protected $pagebrowser = null;

    /**
    * returns class version
    * 
    * @return       version-number (float)
    */
    public static function get_version()
    {
        return ub_pagebrowser::version;
    } // end get_version()

    /**
    * returns class instance
    * 
    * @return       ub_pagebrowser (object)
    */
    public static function get_instance($ident)
    {
        $unibox = ub_unibox::get_instance();
        if (!isset($unibox->session->env->pagebrowser->$ident) || !isset(self::$instances[$ident]))
            self::$instances[$ident] = new ub_pagebrowser($ident);
        return self::$instances[$ident];
    }

    /**
    * class constructor - loads framework configuration
    *
    */
    protected function __construct($ident)
    {
        $this->unibox = ub_unibox::get_instance();
        $this->ident = $ident;

        if (!isset($this->unibox->session->env->pagebrowser->$ident))
        {
            $this->unibox->session->env->pagebrowser->$ident = new stdClass();
            $this->unibox->session->env->pagebrowser->$ident->ident = $ident;
        }

        $this->pagebrowser = &$this->unibox->session->env->pagebrowser->$ident;
    }

    /**
    * process page browsing
    *
    * @param        $sql_string         content sql string (string)
    * @param        $per_page           count of datasets per page (int)
    * 
    */
    function process($sql_string, $count_per_page = 15, $override_count_per_page = false, $allow_count_per_page_change = true)
    {
        // get validator instance
        $validator = ub_validator::get_instance();

        // validate count
        if ($allow_count_per_page_change && $validator->validate('GET', 'pagebrowser_'.$this->ident.'_count', TYPE_INTEGER, CHECK_NOTEMPTY))
            $this->pagebrowser->count_per_page = $this->unibox->session->env->input->{'pagebrowser_'.$this->ident.'_count'};
        elseif (!isset($this->pagebrowser->count_per_page) || $override_count_per_page)
            $this->pagebrowser->count_per_page = $count_per_page;

        // get count of datasets
        if (stristr($sql_string, 'DISTINCT'))
            $sql_substring = 'SELECT COUNT(*) FROM ('.$sql_string.') AS distincted_table';
        else
            $sql_substring = 'SELECT COUNT(*) '.stristr($sql_string, 'FROM');
        $result = $this->unibox->db->query($sql_substring, 'error while trying to select the count of values for a page-browse-string: \''.$sql_string.'\'');
        list($this->pagebrowser->count_datasets) = $result->fetch_row();
        $result->free();

        // get count of pages
        if ($this->pagebrowser->count_per_page > 0)
            $this->pagebrowser->count_pages = ceil($this->pagebrowser->count_datasets / $this->pagebrowser->count_per_page);
        else
            $this->pagebrowser->count_pages = 1;

        // validate page
        if ($validator->form_validate('pagebrowser_'.$this->ident.'_page'))
            $this->pagebrowser->page = $this->unibox->session->env->form->{'pagebrowser_'.$this->ident.'_page'}->data->page;
        else
        {
            $validator->reset();
            $validator->validate('GET', 'pagebrowser_'.$this->ident.'_page', TYPE_INTEGER, CHECK_NOTEMPTY);
            $validator->validate('GET', 'pagebrowser_'.$this->ident.'_page', TYPE_INTEGER, CHECK_INRANGE, null, array(1, $this->pagebrowser->count_pages));
            if ($validator->get_result())
                $this->pagebrowser->page = $this->unibox->session->env->input->{'pagebrowser_'.$this->ident.'_page'};
        }

        if (!isset($this->pagebrowser->page))
            $this->pagebrowser->page = 1;

        if ($this->pagebrowser->page > $this->pagebrowser->count_pages && $this->pagebrowser->count_pages > 0)
            $this->pagebrowser->page = $this->pagebrowser->count_pages;

        if ($this->pagebrowser->count_per_page == 0)
        {
            $this->pagebrowser->begin = 0;
            $this->pagebrowser->end = $this->pagebrowser->count_datasets;
        }
        else
        {
            $this->pagebrowser->begin = ($this->pagebrowser->page - 1) * $this->pagebrowser->count_per_page;
        
            if (($end = ($this->pagebrowser->begin + $this->pagebrowser->count_per_page)) > $this->pagebrowser->count_datasets)
                $this->pagebrowser->end = $this->pagebrowser->count_datasets;
            else
                $this->pagebrowser->end = $end;
        }
    } // end process()

	public function get_limit()
	{
		if ($this->pagebrowser->count_per_page == 0)
			return false;

		return $this->pagebrowser->begin.', '.$this->pagebrowser->count_per_page;
	}

	public function set_page($page = 1)
	{
		$this->pagebrowser->page = $page;
	}

	public function get_page()
	{
		return $this->pagebrowser->page;
	}
	
	public function get_count()
	{
		return $this->pagebrowser->count_per_page;
	}

    /**
    * write page browsing to xml
    *
    * @param        $link               url to link to
    *
    */
    public function show($link)
    {
        // load template
        $this->unibox->load_template('shared_pagebrowser');
        
        // begin node
        $this->unibox->xml->add_node('pagebrowser');
        $this->unibox->xml->set_attribute('ident', $this->ident);

        // add counts
        $this->unibox->xml->add_node('count');
        $this->unibox->xml->add_value('total', $this->pagebrowser->count_datasets);
        foreach (array(5, 15, 25, 50, 100, 0) AS $value)
        {
            $this->unibox->xml->add_node('dataset');
            if ($value > 0)
                $this->unibox->xml->add_value('text', $value);
            else
                $this->unibox->xml->add_value('text', 'TRL_ALL', true);

            if ((isset($this->pagebrowser->count_per_page) && $value == $this->pagebrowser->count_per_page))
                $this->unibox->xml->add_value('link', '');
            else
                $this->unibox->xml->add_value('link', $link.'/pagebrowser_'.$this->ident.'_count/'.$value);
            $this->unibox->xml->parse_node();
        }
        $this->unibox->xml->parse_node();

        if ($this->pagebrowser->count_pages > 1)
        {
            $this->unibox->xml->add_node('pages');
    
            // if we're not on the first page, show links to navigate
            if ($this->pagebrowser->page > 1)
            {
                $this->unibox->xml->add_node('first');
                $this->unibox->xml->add_value('link', $link.'/pagebrowser_'.$this->ident.'_page/1');
                $this->unibox->xml->parse_node();
                
                $this->unibox->xml->add_node('previous');
                $this->unibox->xml->add_value('link', $link.'/pagebrowser_'.$this->ident.'_page/'.($this->pagebrowser->page - 1));
                $this->unibox->xml->parse_node();
            }
    
            // if more then 3 pages exists for- and backwards
            if (($this->pagebrowser->page > 3) && ($this->pagebrowser->page <= ($this->pagebrowser->count_pages - 3)))
            {
                $start = ($this->pagebrowser->page - 3);
                $end = ($this->pagebrowser->page + 3);
            }
            // if less than 3 pages to the end
            elseif (($this->pagebrowser->page > ($this->pagebrowser->count_pages - 3)) && ($this->pagebrowser->count_pages > 3))
            {
                $start = ($this->pagebrowser->count_pages - 6);
                if ($start < 1)
                    $start = 1;
                $end = $this->pagebrowser->count_pages;
            }
            // every other possibility
            else
            {
                $start = 1;
                $end = ($this->pagebrowser->count_pages > 7) ? 7 : $this->pagebrowser->count_pages;
            }
            // build single page entries
            for ($i=$start; $i<=$end; $i++)
            {
                $this->unibox->xml->add_node('dataset');
                $this->unibox->xml->set_attribute('active', (int)($i != $this->pagebrowser->page));
                $this->unibox->xml->add_value('text', $i);
                $this->unibox->xml->add_value('link', $link.'/pagebrowser_'.$this->ident.'_page/'.$i);
                $this->unibox->xml->parse_node();
            }
    
            // if we're not on the last page, show links to navigate
            if ($this->pagebrowser->page < $this->pagebrowser->count_pages)
            {
                $this->unibox->xml->add_node('next');
                $this->unibox->xml->add_value('link', $link.'/pagebrowser_'.$this->ident.'_page/'.($this->pagebrowser->page + 1));
                $this->unibox->xml->parse_node();
                
                $this->unibox->xml->add_node('last');
                $this->unibox->xml->add_value('link', $link.'/pagebrowser_'.$this->ident.'_page/'.$this->pagebrowser->count_pages);
                $this->unibox->xml->parse_node();
            }
            
            $this->unibox->xml->parse_node();
            $this->unibox->xml->add_value('overall_count', $this->pagebrowser->count_pages);

            // add form to directly jump
            $this->unibox->xml->add_node('goto');
            $form = ub_form_creator::get_instance();
            $form->begin_form('pagebrowser_'.$this->ident.'_page', $link);
            $form->text('page');
            $form->set_type(TYPE_INTEGER);
            $form->set_condition(CHECK_NOTEMPTY);
            $form->set_condition(CHECK_INRANGE, array(1, $this->pagebrowser->count_pages));
            $form->submit('TRL_OK');
            $form->end_form();
            $this->unibox->xml->parse_node();
        }
        $this->unibox->xml->parse_node();
        return $this->pagebrowser->count_pages;
    }
}

?>