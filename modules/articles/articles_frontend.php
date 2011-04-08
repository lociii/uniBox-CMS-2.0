<?php

/**
*
* uniBox 2.0 (winterspring)
* (c) Media Soma GbR
*
*/

class ub_articles_frontend
{
    /**
    * $version
    *
    * class version
    * 
    * @access   protected
    */
    const version = '0.1.0';

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
    protected $unibox;

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
        return ub_articles_frontend::version;
    } // get_version

    /**
    * get_instance()
    *
    * return class instance
    * 
    * @access   public
    * @return   object      ub_articles_frontend object
    */
    public static function get_instance()
    {
        if (is_null(self::$instance))
            self::$instance = new ub_articles_frontend;
        return self::$instance;
    } // get_instance

    /**
    * __construct()
    *
    * constructor - called everytime the objects get instantiated
    * get framework instance and load config
    * 
    * @access   public
    */
    public function __construct()
    {
        $this->unibox = ub_unibox::get_instance();
        $this->unibox->config->load_config('articles');
    } // __construct

    /**
    * article
    *
    * display a single article
    * 
    * @access   public
    */
	public function article()
	{
		try
		{
	    	$categories = $this->unibox->session->get_allowed_categories('articles_show');
	    	if (empty($categories))
	    		throw new ub_exception_general('no valid categories found display single article', 'TRL_ERR_NO_RIGHTS_FOR_ANY_ARTICLES');

	        $validator = ub_validator::get_instance();

	        // check for language
	        $sql_string  = 'SELECT
	                          lang_ident
	                        FROM
	                          sys_languages
	                        WHERE
	                          lang_active = 1';
	        if ($validator->validate('GET', 'lang_ident', TYPE_STRING, CHECK_INSET_SQL, null, $sql_string))
	            $lang_ident = $this->unibox->session->env->input->lang_ident;
	        elseif ($validator->validate('GET', 'lang_ident', TYPE_STRING, CHECK_ISSET, 'TRL_ERR_INVALID_LANGUAGE_PASSED'))
	        {
	            $this->unibox->display_error();
	            return;
	        }
	        else
	            $lang_ident = $this->unibox->session->lang_ident;

	        // article set via alias
	        if (isset($this->unibox->session->env->alias->get['article_id']))
	            $article_id = $this->unibox->session->env->alias->get['article_id'];
	        // article set via url
	        elseif ($validator->validate('GET', 'article_id', TYPE_INTEGER, CHECK_ISSET))
	            $article_id = $this->unibox->session->env->input->article_id;

	        // check if given article exists and is allowed to be viewed
			$time = new ub_time(TIME_TYPE_SERVER, TIME_TYPE_DB);
			$time->now();
			$now = $time->get_datetime();

			if ($editorial_version = $validator->validate('GET', 'editorial_version', TYPE_STRING, CHECK_ISSET))
				$type = 'editorial';
			else
				$type = 'live';

	        $sql_string  = 'SELECT
							  a.articles_container_id,
	                          b.article_'.$type.'_title,
	                          b.article_'.$type.'_message,
	                          a.articles_container_show_begin,
	                          c.user_name,
	                          a.category_id,
	                          e.string_value
	                        FROM
	                          data_articles_container AS a
	                            INNER JOIN data_articles AS b
	                              ON
								  (
								  b.articles_container_id = a.articles_container_id
								  AND
								  b.lang_ident = \''.$lang_ident.'\'
								  )
	                            INNER JOIN sys_users AS c
	                              ON c.user_id = b.user_id
	                            INNER JOIN sys_categories AS d
	                              ON d.category_id = a.category_id
	                            INNER JOIN sys_translations AS e
	                              ON
								  (
								  e.string_ident = d.si_category_name
								  AND
								  e.lang_ident = \''.$lang_ident.'\'
								  )
	                        WHERE
	                          (
	                          a.articles_container_show_begin <= \''.$now.'\'
	                          OR
	                          a.articles_container_show_begin IS NULL
	                          )
	                          AND
	                          (
	                          a.articles_container_show_end > \''.$now.'\'
	                          OR
	                          a.articles_container_show_end IS NULL
	                          )
	                          AND
	                          a.category_id IN ('.implode(', ', $this->unibox->session->get_allowed_categories('articles_show')).')';
			if (isset($article_id))
				$sql_string .= ' AND a.articles_container_id = '.$article_id;
			else
				$sql_string .= ' ORDER BY a.articles_container_show_begin DESC LIMIT 0, 1';

	        $result = $this->unibox->db->query($sql_string, 'failed to check if given article exists');
	        if ($result->num_rows() != 1)
	        	throw new ub_exception_general('no dataset found');

			// get data
	        list($article_id, $title, $message, $date, $author, $category_id, $category_name) = $result->fetch_row();

			// check if content exists
			if ($title === null && $message === null)
				throw new ub_exception_general('no content found');

			// set getvar to hightlight menu
			if (!$this->unibox->within_extension)
	        	$this->unibox->session->env->alias->main_get['article_id'] = $article_id;

			// free database result
	        $result->free();

	        // load category details
	        $this->unibox->config->load_category($category_id);
	        $this->unibox->xml->add_value('alias_multiple', $this->unibox->config->category->$category_id->alias_multiple);

			// extend location
	        if (!$this->unibox->within_extension)
	        {
	            // print category to location
	            if ($this->unibox->config->category->$category_id->location_show_category)
	            {
	                $this->unibox->xml->goto('location');
	                $this->unibox->xml->add_node('component');
	                $this->unibox->xml->add_value('value', $category_name);
	                $this->unibox->xml->parse_node();
	                $this->unibox->xml->restore();
	            }

	            $this->unibox->set_content_title($title, array(), false);
	            $this->unibox->xml->goto('location');
	            $this->unibox->xml->add_node('component');
	            $this->unibox->xml->add_value('value', $title);
	            $this->unibox->xml->parse_node();
	            $this->unibox->xml->restore();
	        }

			// output dataset
			$this->unibox->xml->add_value('article_id', $article_id);
			$this->unibox->xml->add_value('category_id', $category_id);
	        $this->unibox->xml->add_value('category_name', $category_name);
	        $this->unibox->xml->add_value('title', $title);
	        $this->unibox->xml->add_node('message');
	        $ubc = new ub_ubc();
	        $ubc->ubc2xml($message);
	        // $this->unibox->xml->import_xml($message);
	        $this->unibox->xml->parse_node();
	        if ($this->unibox->config->category->$category_id->detail_show_date)
	        {
	            $time = new ub_time(TIME_TYPE_DB, TIME_TYPE_USER);
	            $time->parse_datetime($date);
	            $this->unibox->xml->add_node('time');
	            $time->get_xml($this->unibox->xml);
	            $this->unibox->xml->parse_node();
	        }

	        if ($this->unibox->config->category->$category_id->detail_show_author)
	            $this->unibox->xml->add_value('author', $author);
	        $this->unibox->session->var->register('articles_showed_article', $article_id, true);
		}
		catch (ub_exception_general $exception)
		{
			if ($editorial_version)
				$exception->process('TRL_ERR_NO_EDITORIAL_VERSION_FOUND');
			else
				$exception->process('TRL_ERR_NO_LIVE_VERSION_FOUND');
		}
		$this->unibox->load_template('articles_show_one');
	}

    /**
    * category()
    *
    * display a category with subcategories and datasets
    * 
    * @access   public
    */
    public function category()
    {
    	try
    	{
    		$categories_found = $single_category = false;
    		
	    	$categories = $this->unibox->session->get_allowed_categories('articles_show');
	    	if (count($categories) == 0)
	    		throw new ub_exception_general('no rights', 'TRL_ERR_NO_RIGHTS_FOR_ANY_ARTICLES');
	    	
	        $validator = ub_validator::get_instance();
	        // check for language
	        $sql_string  = 'SELECT
	                          lang_ident
	                        FROM
	                          sys_languages
	                        WHERE
	                          lang_active = 1';
	        if ($validator->validate('GET', 'lang_ident', TYPE_STRING, CHECK_INSET_SQL, null, $sql_string))
	            $lang_ident = $this->unibox->session->env->input->lang_ident;
	        elseif ($validator->validate('GET', 'lang_ident', TYPE_STRING, CHECK_ISSET, 'TRL_ERR_INVALID_LANGUAGE_PASSED'))
	        {
	            $this->unibox->display_error();
	            return;
	        }
	        else
	            $lang_ident = $this->unibox->session->lang_ident;

	        // category set via alias
	        if (isset($this->unibox->session->env->alias->get['category_id']))
	            $category_id = $this->unibox->session->env->alias->get['category_id'];
	        elseif ($validator->validate('GET', 'category_id', TYPE_INTEGER, CHECK_ISSET))
	            $category_id = $this->unibox->session->env->input->category_id;

	        // set content title
	        // print action to location
	        if (!$this->unibox->within_extension)
	        {
	            $this->unibox->set_content_title('TRL_ARTICLES_OVERVIEW');
	            $this->unibox->xml->goto('location');
	            $this->unibox->xml->add_node('component');
	            $this->unibox->xml->add_value('value', 'TRL_ARTICLES_OVERVIEW', true);
	            $this->unibox->xml->parse_node();
	            $this->unibox->xml->restore();
	        }

	        if (isset($category_id))
	        {
	        	$single_category = true;
	        	$this->unibox->config->load_category($category_id);
	        	
	        	// select category name
		        $sql_string  = 'SELECT
		                          b.string_value
		                        FROM
		                          sys_categories AS a
		                            INNER JOIN sys_translations AS b
		                              ON b.string_ident = a.si_category_name
		                        WHERE
		                          a.category_id = '.$category_id.'
		                          AND
		                          b.lang_ident = \''.$lang_ident.'\'';
		        $result = $this->unibox->db->query($sql_string, 'failed to select category name');
		        if ($result->num_rows() > 0)
		        {
		            list($category_name) = $result->fetch_row();
		            // set category and print name to location
		            if (!$this->unibox->within_extension && $this->unibox->config->category->$category_id->location_show_category)
		            {
		            	$this->unibox->set_content_title('TRL_ARTICLES_OVERVIEW_CATEGORY', array($category_name));
		                $this->unibox->xml->goto('location');
		                $this->unibox->xml->add_node('component');
		                $this->unibox->xml->add_value('value', $category_name);
		                $this->unibox->xml->parse_node();
		                $this->unibox->xml->restore();
		            }
		            $result->free();
		        }
	        }

			// check for subcategories to show
	        if ((isset($category_id) && $this->unibox->config->category->$category_id->show_subcategories) || (!isset($category_id) && $this->unibox->config->system->articles_show_subcategories))
	        {
	            // show subcategories
	            $sql_string  = 'SELECT
	                              a.category_id,
	                              b.string_value
	                            FROM
	                              sys_categories AS a
	                                INNER JOIN sys_translations AS b
	                                  ON b.string_ident = a.si_category_name
	                            WHERE
	                              b.lang_ident = \''.$lang_ident.'\'
	                              AND
	                              a.category_parent_id '.((isset($category_id)) ? '= '.$category_id : 'IS NULL').'
	                              AND
	                              a.module_ident = \'articles\'
	                            ORDER BY
	                              b.string_value ASC';
	            $result = $this->unibox->db->query($sql_string, 'failed to select subcategories');
	            if ($result->num_rows() > 0)
	            {
	                while (list($subcategory_id, $name) = $result->fetch_row())
	                    if ($this->unibox->session->has_right('articles_show', $subcategory_id))
	                    {
	                    	$this->unibox->config->load_category($subcategory_id);

	                        $categories_found = true;
	                        $this->unibox->xml->add_node('category');
	                        $this->unibox->xml->add_value('alias', $this->unibox->config->category->$subcategory_id->alias_multiple);
	                        $this->unibox->xml->add_value('id', $subcategory_id);
	                        $this->unibox->xml->add_value('name', $name);
	                        $this->unibox->xml->parse_node();
	                    }
	            	$result->free();
	            }
	        }

			$time = new ub_time(TIME_TYPE_SERVER, TIME_TYPE_DB);
			$time->now();
			$now = $time->get_datetime();

	        // build query
	        $sql_string  = 'SELECT
	                          a.articles_container_id,
	                          b.article_live_title,
							  b.article_live_message,
	                          a.articles_container_show_begin,
	                          c.user_name,
							  a.category_id,
							  e.string_value
	                        FROM
	                          data_articles_container AS a
	                            INNER JOIN data_articles AS b
	                              ON
								  (
								  b.articles_container_id = a.articles_container_id
								  AND
								  b.lang_ident = \''.$lang_ident.'\'
								  )
	                            INNER JOIN sys_users AS c
	                              ON c.user_id = b.user_id
								INNER JOIN sys_categories AS d
								  ON d.category_id = a.category_id
	                            INNER JOIN sys_translations AS e
	                              ON
								  (
								  e.string_ident = d.si_category_name
								  AND
								  e.lang_ident = \''.$this->unibox->session->lang_ident.'\'
								  )
	                        WHERE
	                          (
	                          a.articles_container_show_begin <= \''.$now.'\'
	                          OR
	                          a.articles_container_show_begin IS NULL
	                          )
	                          AND
	                          (
	                          a.articles_container_show_end > \''.$now.'\'
	                          OR
	                          a.articles_container_show_end IS NULL
	                          )
	                          AND
	                          a.category_id IN ('.implode(', ', $categories).')';
			// limit query to one category if set
			if (isset($category_id))
				$sql_string .= ' AND a.category_id = '.$category_id;

			// don't list detailled showed articles
			if (isset($this->unibox->session->var->articles_showed_article) && $this->unibox->config->system->articles_exclude_from_overview)
				$sql_string .= ' AND a.articles_container_id != '.$this->unibox->session->var->articles_showed_article;

			// set order
			if (isset($category_id))
	        	$sql_string .= ' ORDER BY '.$this->unibox->config->category->$category_id->sort;
        	else
        		$sql_string .= ' ORDER BY '.$this->unibox->config->system->articles_sort;

			// add pagebrowser
	        $pagebrowser = ub_pagebrowser::get_instance('articles_show_category');
	        $pagebrowser->process($sql_string, (isset($category_id) ? $this->unibox->config->category->$category_id->overview_show_count : $this->unibox->config->system->articles_overview_show_count));
	        $sql_string .= ' LIMIT '.$pagebrowser->get_limit();
	        if (isset($category_id))
	        	$pagebrowser->show($this->unibox->config->category->$category_id->alias_multiple.'/category_id/'.$category_id);
	        else
	        	$pagebrowser->show($this->unibox->identify_alias('articles_show_category'));

			// query data
	        $result = $this->unibox->db->query($sql_string, 'failed to get articles');
	        if ($result->num_rows() > 0)
	        {
	        	$time = new ub_time(TIME_TYPE_DB, TIME_TYPE_USER);
	        	$this->unibox->xml->add_value('single_category', $single_category ? '1' : '0');
	            while (list($id, $title, $articles_message, $date, $author, $category_id, $category_name) = $result->fetch_row())
	            {
	            	$this->unibox->config->load_category($category_id);
	            	
	                $this->unibox->xml->add_node('article');
	                $this->unibox->xml->add_value('id', $id);
		            $this->unibox->xml->add_value('category_id', $category_id);
		            $this->unibox->xml->add_value('category_name', $category_name);
	                $this->unibox->xml->add_value('title', $title);
	                $this->unibox->xml->add_value('alias', $this->unibox->config->category->$category_id->alias_one);

					if ($this->unibox->config->category->$category_id->overview_show_message)
					{
	                	$this->unibox->xml->add_node('message');
				        $ubc = new ub_ubc();
				        $ubc->ubc2xml($articles_message);
				        // $this->unibox->xml->import_xml($articles_message);
				        $this->unibox->xml->parse_node();
					}

	                if ($this->unibox->config->category->$category_id->overview_show_date)
	                {
	                    $time->reset();
	                    $time->parse_datetime($date);
	                    $this->unibox->xml->add_node('time');
	                    $this->unibox->xml->add_value('date', $time->get_date());
	                    $this->unibox->xml->add_value('time', $time->get_time());
	                    $this->unibox->xml->add_value('datetime', $time->get_datetime());
	                    $this->unibox->xml->add_value('weekday', $time->get_weekday());
	                    $this->unibox->xml->add_value('word', $time->get_word());
	                    $this->unibox->xml->parse_node();
	                }
	                if ($this->unibox->config->category->$category_id->overview_show_author)
	                    $this->unibox->xml->add_value('author', $author);
	                $this->unibox->xml->parse_node();
	            }
	            $result->free();
	        }
	        // nothing found
	        elseif (!$categories_found)
	        	throw new ub_exception_general('no datasets nor subcategories found', 'TRL_ERR_NO_ARTICLES_NOR_SUBCATEGORIES_FOUND');
    	}
    	catch (ub_exception_general $exception)
    	{
    		$exception->process();
    		return;
    	}
    	$this->unibox->load_template('articles_show_more');
    }
}

?>