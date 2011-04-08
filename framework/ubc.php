<?php

/**
*
* uniBox 2.0 (winterspring)\n
* (c) Media Soma GbR
*
* 0.1       12.08.2005  jn/pr		1st release
* 0.2		20.03.2006	pr			lots of improvements
* 0.21		21.03.2006	pr			fixed font color
*
*/

class ub_ubc
{
    /**
    * class version
    * 
    */
    const version = '0.1.0';

    /**
    * unibox framework instance
    * 
    */
    protected $unibox;

    /**
    * ub_xml instance for data adding
    * 
    */
    protected $xml;

    /**
    * stack to save open ubc tags
    * 
    */
    protected $ubc_stack = array();

    /**
    * stack to save open html tags
    * 
    */
    protected $html_stack = array();

    /**
    * smiley definitions (code)
    * 
    */
    protected $smiley_codes = array();

    /**
    * smiley definitions (files)
    * 
    */
    protected $smiley_files = array();

    /**
    * standard ubc tags (with opener and closer)
    * 
    */
    protected $ubc_std_tags = array('url',
                                    'sup',
                                    'sub',
                                    'code',
                                    'fixed',
                                    'quote',
                                    'list',
                                    'listentry',
                                    'email',
                                    'color',
                                    'size',
                                    'strong',
                                    'italic',
                                    'underline',
                                    'strike',
                                    'align',
                                    'abbr',
                                    'lang',
                                    'table',
                                    'table_head',
                                    'table_body',
                                    'table_foot',
                                    'table_row',
                                    'table_cell_head',
                                    'table_cell',
                                    'smiley'
                                    );

    /**
    * selfclosing ubc tags
    * 
    */
    protected $ubc_sc_tags = array( 'img',
                                    'anchor',
                                    'separator',
                                    'br',
                                    'flash',
                                    'replace',
                                    'smiley'
                                    );

    /**
    * allowed html tags and their ubc correspondants
    * 
    */
    protected $html_tags = array(   'a',        // url, email, anchor
                                    'sup',      // sup
                                    'sub',      // sub
                                    'ul',       // list listtype=ul/empty
                                    'ol',       // list listtype=ol
                                    'li',       // listentry
                                    'span',     // color, size, lang, text-align
                                    'div',      // color, size, lang, text-align
                                    'strong',   // strong
                                    'em',       // italic
                                    'u',        // underline
                                    'strike',   // strikethrough
                                    'acronym',  // abbr
                                    'p',        // align
                                    'div',      // align
                                    'img',      // img
                                    'table',    // table
                                    'thead',    // table_head
                                    'tbody',    // table_body
                                    'tfoot',    // table_foot
                                    'th',       // table_row_head
                                    'tr',       // table_row
                                    'td',       // table_cell
                                    'caption',  // table_caption
                                    'font',     // color, size
                                    'br',       // br
                                    'embed',    // flash
                                    'object',   // allow for flash but kick
                                    'param',    // allow for flash but kick
                                    'hr'		// separator
                                    ); // missing: code, fixed, quote

    /**
    * required attributes for ubc tags
    * 
    */
    protected $ubc_req_attr = array('anchor'            => array('name'),
                                    'url'               => array('href'),
                                    'email'             => array('href'),
                                    'abbr'              => array('title'),
                                    'lang'              => array('lang'),
                                    'color'             => array('value'),
                                    'size'              => array('value'),
                                    'align'             => array('value'),
                                    'img'               => array('themes'),
                                    'flash'             => array('src'),
                                    'smiley'            => array('src')
                                    );

    /**
    * optional attributes for ubc tags
    * 
    */
    protected $ubc_opt_attr = array('abbr'          => array(   'lang'
                                                            ),
                                    'quote'         => array(   'author',
                                                                'url'
                                                            ),
                                    'list'          => array(   'listtype',
                                                                'vistype'
                                                            ),
                                    'url'           => array(   'lang',
                                                                'hreflang',
                                                                'title',
                                                                'dir',
                                                                'tabindex',
                                                                'accesskey',
                                                                'onclick',
                                                                'rel'
                                                            ),
                                    'email'         => array(   'descr'
                                                            ),
                                    'img'           => array(   'float',
                                                                'border',
                                                                'width',
                                                                'height',
                                                                'margin-left',
                                                                'margin-right',
                                                                'margin-top',
                                                                'margin-bottom',
                                                                'zoom'
                                                            ),
                                    'table'         => array(   'cellpadding',
                                                                'cellspacing',
                                                                'text-align',
                                                                'border',
                                                                'width',
                                                                'height',
                                                                'summary',
                                                                'dir',
                                                                'lang',
                                                                'class',
                                                                'background-color'
                                                            ),
                                    'table_row'     => array(   'vertical-align',
                                                                'text-align',
                                                                'class',
                                                                'height',
                                                                'dir',
                                                                'lang',
                                                                'background-color'
                                                            ),
                                    'table_cell_head'=> array(  'vertical-align',
                                                                'text-align',
                                                                'class',
                                                                'scope',
                                                                'width',
                                                                'height',
                                                                'dir',
                                                                'lang',
                                                                'background-color',
                                                                'rowspan',
                                                                'colspan'
                                                            ),
                                    'table_cell'    => array(   'vertical-align',
                                                                'text-align',
                                                                'class',
                                                                'width',
                                                                'height',
                                                                'dir',
                                                                'lang',
                                                                'background-color',
                                                                'rowspan',
                                                                'colspan'
                                                            ),
                                    'flash'         => array(   'width',
                                                                'height'
                                                            )
                                    );

    /**
    * merged array of required and optional tags
    * 
    */
    protected $ubc_tags = array();

    /**
    * to ubc reformatted html input
    * 
    */
    protected $ubc_text = '';

    /**
    * returns class version
    * 
    * @return       version-number (float)
    */
    public static function get_version()
    {
        return ub_ubc::version;
    } // end get_version()
    
    /**
    * class constructor
    */
    public function __construct()
    {
        $this->unibox = ub_unibox::get_instance();
        $this->unibox->load_template('shared_ubc');
        $this->ubc_tags = array_merge($this->ubc_std_tags, $this->ubc_sc_tags);
    } // end __construct()

    /**
    * modified version of strtok() to simulate strtok-behaviour of PHP <= 4.1.0
    * 
    * @param        $delim              delimiting character (string)
    * @param        $string             string to be tokenized (string)
    * @return       token
    */
    protected function get_token($delim, $string = null)
    {
        static $org_string, $org_pos;
        if ($string === null)
        {
            // continuing an already started strtok
            $string = $org_string;
            if ($org_pos >= strlen($string) || $string == null)
                return false;
        }
        else
        {
            // else starting from scratch
            $org_string = $string;
            $org_pos = 0;
        }
    
        $new_pos = strpos($string, $delim, $org_pos);
        if ($new_pos === false)
            $new_pos = strlen($string);
        
        $return = substr($string, $org_pos, ($new_pos - $org_pos));
        $org_pos = ++$new_pos;
        return $return;
    } // end ubc_strtok()

    #################################################################################################
    ### ubc functions
    #################################################################################################

	protected function all2html_prepare($theme_ident = null, $subtheme_ident = null, $styles = array())
	{
		// save data
		$data['xml'] = $this->unibox->xml;
		$data['output_format_ident'] = $this->unibox->session->output_format_ident;
		$data['templates'] = $this->unibox->get_templates();
		$data['templates_content'] = $this->unibox->get_templates_content();
		$data['templates_extension'] = $this->unibox->get_templates_extension();

		if ($theme_ident == null)
			$theme_ident = $this->unibox->session->env->themes->current->theme_ident;

		if ($subtheme_ident == null)
			$subtheme_ident = $this->unibox->session->env->themes->current->subtheme_ident;

		if (count($styles) == 0)
			$styles[] = 'main_email';

		// unload all templates
		$this->unibox->unload_templates();

		// create a new one
		$this->unibox->session->output_format_ident = 'xhtml';
		$this->unibox->xml = new ub_xml;
        $this->unibox->xml->add_node('unibox');
        $this->unibox->xml->set_marker('unibox');
        $this->unibox->xml->add_value('html_base', $this->unibox->session->html_base);
        $this->unibox->xml->add_value('theme_ident', $theme_ident);
        $this->unibox->xml->add_value('subtheme_ident', $subtheme_ident);
        $this->unibox->xml->add_value('combined_theme_ident', $theme_ident.'_'.$subtheme_ident);
        $this->unibox->xml->add_value('editor_mode', (int)$this->unibox->editor_mode);
        $this->unibox->xml->parse_node();

		$data['xsl'] = new ub_xsl;
		$this->unibox->load_template('show_ubc');
		$this->unibox->load_template('shared_ubc');
		
		return $data;
	}

	protected function all2html_restore($data)
	{
		// restore xml and templates
		$this->unibox->xml = $data['xml'];
		$this->unibox->session->output_format_ident = $data['output_format_ident'];
		$this->unibox->set_templates($data['templates']);
		$this->unibox->set_templates_content($data['templates_content']);
		$this->unibox->set_templates_extension($data['templates_extension']);
	}

    /**
     * transforms ub-code to html-code
     * 
     * @param		$ubc_str			ub-code to be transformed
     * @param		$theme_ident		theme ident to be used
     * @param		$subtheme_ident		subtheme_ident to be used
     * @param		$styles				styles to be used
     * @return		html-code
     */
    public function ubc2html($ubc_str, $theme_ident = null, $subtheme_ident = null, $styles = array())
    {
    	$html = '';
    	
		$data = $this->all2html_prepare($theme_ident, $subtheme_ident, $styles);

		// process ubc and xsl
        $ubc = new ub_ubc();
        $this->unibox->xml->add_node('content');
		$ubc->ubc2xml($ubc_str);
        $this->unibox->xml->parse_node();
		$html = $data['xsl']->process($this->unibox->xml);

		// restore xml and templates
		$this->all2html_restore($data);

		return $html;
    } // end ubc2html()

    /**
     * transforms ub-code to html-code
     * 
     * @param		$ubc_str			ub-code to be transformed
     * @param		$theme_ident		theme ident to be used
     * @param		$subtheme_ident		subtheme_ident to be used
     * @param		$styles				styles to be used
     * @return		html-code
     */
    public function xml2html($xml_str, $theme_ident = null, $subtheme_ident = null, $styles = array())
    {
    	$html = '';
    	
		$data = $this->all2html_prepare($theme_ident, $subtheme_ident, $styles);

		// process ubc and xsl
        //$ubc = new ub_ubc();
        $this->unibox->xml->add_node('content');
        $this->unibox->xml->import_xml($xml_str);
		//$ubc->ubc2xml($ubc_str);
        $this->unibox->xml->parse_node();
		$html = $data['xsl']->process($this->unibox->xml);

		// restore xml and templates
		$this->all2html_restore($data);

		return $html;
    } // end ubc2html()

    /**
    * transforms ub-code to xml
    * 
    * @param        $text               text to search for ubc-tags (string)
    * @param        $xml                what xml to extend (DOM XML Document)
    */
    function ubc2xml($text, &$xml = null, $parse_smileys = true)
    {
        if ($xml === null)
            $this->xml = $this->unibox->xml;
        else
            $this->xml = &$xml;

        if ($this->unibox->editor_mode)
        {
            $sql_string  = 'SELECT
                              id,
                              code
                            FROM
                              sys_smileys';
            $result = $this->unibox->db->query($sql_string, 'failed to select smileys');
            if ($result->num_rows() > 0)
            {
                $patterns = $replacements = array();
                while (list($id, $code) = $result->fetch_row())
                {
                    $patterns[] = '/\[smiley id="'.$id.'" \/\]/';
                    $replacements[] = $code;
                }
                $result->free();
                $text = preg_replace($patterns, $replacements, $text);
            }
        }

		// add ubc root node
		$this->xml->add_node('ubc');
		$this->xml->set_attribute('xmlns:ubc', 'http://www.media-soma.de/ubc');

        $this->ubc_stack = array();
        $tok = $this->get_token('[', $text);
        while ($tok !== false)
        {
            if (end($this->ubc_stack) != 'list')
                $this->xml->add_text($tok);
            $this->ubc_process_tag($this->get_token(']'));
            $tok = $this->get_token('[');
        }

        // close all remaining tags
        for ($i = 0; $i < count($this->ubc_stack); $i++)
            $this->xml->parse_node();

		$this->xml->parse_node();
    } // end ubc2xml()

    public function html2xml($text, $parse_smileys = true)
    {
    	$xml = new ub_xml();
        $text = $this->html2ubc($text);
        $this->ubc2xml($text, $xml, $parse_smileys);
        return $xml;
    }

	/**
	 * dissects ubc tags
	 * 
	 * @param		$str		string to dissect
	 * @return 		tag object
	 */
	protected function ubc_dissect_tag($str)
	{
        $str = trim($str);
        if ($str == '')
            return false;

		$tag = new stdClass();
        if ($str[0] == '/')
        {
            $tag->type = TAG_CLOSING;
            $tag->tag = strtolower(strtok(substr($str, 1), ' '));
        }
        else
        {   
            $tag->type = TAG_OPENING;
            $tag->tag = strtolower(strtok($str, ' '));
        }
        
        // check if we got a selfcontained tag
        if (in_array($tag->tag, $this->ubc_sc_tags))
        	$tag->type = TAG_SELFCONTAINED;

		if ($tag->type != TAG_CLOSING)
		{
			$matches = array();
            preg_match_all('/([a-zA-Z-]+)=((\")|(\'))(.*?[^\\\])(?(3)\"|\') ?/', strtok(''), $matches);

            // build attributes array
            if (count($matches[0]) != 0)
            {
                $tag->attributes = array_combine(array_map('strtolower', $matches[1]), array_map('stripslashes', $matches[5]));
                unset($tag->attributes['type']);
            }
            else
                $tag->attributes = array();
		}

		return $tag;
	} // end ubc_dissect_tag()
	
    /**
    * processes ubc-tags
    * 
    * @param        $str                ubc token to be processed to xml (string)
    */
    protected function ubc_process_tag($str)
    {
		if (($tag = $this->ubc_dissect_tag($str)) === false)
			return;

        // check if we're in a code or fixed section and if we got a valid tag
        if ((end($this->ubc_stack) == 'code' && strtolower($str) != '/code') || (end($this->ubc_stack) == 'fixed' && strtolower($str) != '/fixed') || !in_array($tag->tag, $this->ubc_tags))
        {
            $this->xml->add_text('['.$str.']');
            return;
        }

        if ($tag->type != TAG_CLOSING)
        {
            // add base path to relative urls
            // FIX: match in editor
            if (!$this->unibox->editor_mode && $tag->tag == 'url' && isset($tag->attributes['onclick']) && !preg_match('/window\.open\(\'(.*):\/\/([^\']*)\'.*\)/', stripslashes($tag->attributes['onclick'])))
                $tag->attributes['onclick'] = preg_replace('/window\.open\(\'([^\']*)\'(.*)\)/e', '"window.open(\'".$this->unibox->create_link(\'\1\')."\'".stripslashes(\'\2\').")"', $tag->attributes['onclick']);

            // process image
            if ($tag->tag == 'img')
            {
                // don't show image if no themes set
                if (!isset($tag->attributes['themes']))
                    return;

                // match single themes
            	$themes = array();
				if (!preg_match_all('/([a-z_]+): ?(\d+)\|(\d+)\|(\d+); ?/', $tag->attributes['themes'], $themes))
                    return;
                
                // NEVER READ THIS LINE
                $themes = array_combine(array_map('strtolower', $themes[1]), array_map(create_function('$value1, $value2, $value3', 'return array($value1, $value2, $value3);'), $themes[2], $themes[3], $themes[4]));
                unset($tag->attributes['themes']);
            }

			// add ubc component
            //$this->xml->add_node('ubc:'.$tag->tag);
            $this->xml->add_node($tag->tag);

            if ($tag->tag == 'img')
            {
            	if (!$this->unibox->editor_mode)
            		unset($tag->attributes['curtheme']);

            	// check if session is using cookies
            	if (!$this->unibox->session->uses_cookies)
            	{
            		$this->xml->set_attribute('session_name', SESSION_NAME);
            		$this->xml->set_attribute('session_id', $this->unibox->session->session_id);
            	}

                // loop through theme-subtheme-combinations
                $sql_string  = 'SELECT
                                  a.theme_ident,
                                  b.subtheme_ident
                                FROM
                                  sys_themes AS a
                                    INNER JOIN sys_subthemes AS b
                                      ON b.theme_ident = a.theme_ident';
                $result = $this->unibox->db->query($sql_string, 'failed to select themes');
                if ($result->num_rows() > 0)
                {
                    while (list($theme_ident, $subtheme_ident) = $result->fetch_row())
                    {
                    	$combined_theme = $theme_ident.'_'.$subtheme_ident;

                        // check if theme is set
                        if (isset($themes[$combined_theme]))
                        {
                            $this->xml->add_node('theme');
                            $this->xml->set_attribute('name', $combined_theme);
                            $this->xml->add_value('media_id', $themes[$combined_theme][0]);
                            $this->xml->add_value('width', $themes[$combined_theme][1]);
                            $this->xml->add_value('height', $themes[$combined_theme][2]);

	                        $sql_string  = 'SELECT
											  a.category_id,
	                                          b.media_name,
	                                          b.media_descr_short,
	                                          b.media_descr,
	                                          a.category_id,
	                                          a.file_extension,
	                                          a.media_width,
	                                          a.media_height
	                                        FROM
	                                          data_media_base AS a
	                                            LEFT JOIN data_media_base_descr AS b
	                                              ON b.media_id = a.media_id
	                                        WHERE
	                                          a.media_id = '.$themes[$combined_theme][0].'
	                                          AND
	                                          (
	                                          b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
	                                          OR
	                                          b.lang_ident IS NULL
	                                          )';
	                        $result_image = $this->unibox->db->query($sql_string, 'failed to select image description');
	                        if ($result_image->num_rows() == 1)
	                        {
	                            list($category_id, $media_name, $media_descr_short, $media_descr, $category_id, $media_file_extension, $media_width, $media_height) = $result_image->fetch_row();

								if (!empty($media_width) && !empty($media_height))
								{
	                            	$this->xml->add_value('width_orig', $media_width);
	                            	$this->xml->add_value('height_orig', $media_height);
								}
	                            if (!empty($media_name))
	                                $this->xml->add_value('title', $media_name);
	                            if (!empty($media_descr_short))
	                                $this->xml->add_value('alt', $media_descr_short);
	                            if (!empty($media_descr))
	                                $this->xml->add_value('longdesc', 'media_description/media_id/'.$themes[$combined_theme][0]);
	                        }
	                        $result_image->free();
	                        $this->xml->parse_node();
                        }
                    }
                }
                $result->free();
            }
            elseif ($tag->tag == 'replace')
            {
    			// delete replace-node
				$this->xml->remove();

            	if ($this->unibox->editor_mode)
            	{
            	    $this->xml->add_text('['.$str.']');
            		return;
            	}
            	
            	if (($data = $this->ubc_process_replacement($tag->attributes)) !== false)
            		$this->xml->add_text($data);
            		
            	return;
            }
            elseif ($tag->tag == 'email' && !$this->unibox->editor_mode)
            {
        		$this->xml->set_attribute('obfuscate', $this->unibox->config->system->obfuscate_email_adresses);
        		list($user, $host) = explode('@', $tag->attributes['href']);
        		$user = explode('.', $user);
        		$host = explode('.', $host);
        		foreach ($user as $user_part)
        			$this->xml->add_value('user', $user_part);
        		foreach ($host as $host_part)
        			$this->xml->add_value('host', $host_part);
            }

			// process attributes
            foreach ($tag->attributes as $attribute => $value)
            {
                if ($tag->tag == 'flash' && $attribute == 'src')
                	$id = str_replace('media.php5?media_id=', '', $value);
                elseif ($tag->tag == 'url' && $attribute == 'href' && !$this->unibox->editor_mode)
                {
                	$matches = array();
                	if (preg_match('/\[(replace [^\]]+)\]/i', urldecode($value), $matches))
                	{
                		$replace_tag = $this->ubc_dissect_tag($matches[1]);
                		if ($replace_tag !== false)
                			if (($data = $this->ubc_process_replacement($replace_tag->attributes)) !== false)
                				$value = $data;
                	}
                }
                $this->xml->set_attribute($attribute, $value);
            }

            // check if we got a self-closing tag
            if ($tag->type == TAG_SELFCONTAINED)
                $this->xml->parse_node();
            else
                $this->ubc_stack[] = $tag->tag;
        }
        else
        {
            if (end($this->ubc_stack) != $tag->tag)
            {
                if (!in_array($tag->tag, $this->ubc_stack))
                	return;
                
                $len = 0;
                $rev_stack = array_reverse($this->ubc_stack);
                foreach ($rev_stack AS $cl_tag)
                {
                    $len--;
                    $this->xml->parse_node();
                    if ($cl_tag == $tag->tag)
                    	break;
                }
                $this->ubc_stack = array_slice($this->ubc_stack, 0, $len);
            }
            else
            {
                $this->xml->parse_node();
                $this->ubc_stack = array_slice($this->ubc_stack, 0, -1);
            }
        }
    } // end ubc_process_tag()

	/**
	 * retrieves content from unibox
	 * 
	 * @param		$attributes			attributes of replace-tag
	 * @return		replacement value
	 */
    protected function ubc_process_replacement($attributes)
    {
    	if (isset($attributes['module']) && isset($attributes['content']))
    	{
    		if ($attributes['module'] == '__static')
    		{
    			if (isset($this->unibox->session->env->replace->{$attributes['content']}))
					return $this->unibox->session->env->replace->{$attributes['content']};
    		}
    		elseif (isset($attributes['constraint']))
    		{
				$sql_string =  'SELECT
								  entity_value
								FROM
								  sys_sex
								WHERE
								  module_ident_from = \''.$this->unibox->db->cleanup($attributes['module']).'\'
								  AND
								  module_ident_to = \'newsletter\'';
				$result = $this->unibox->db->query($sql_string, 'failed to select entity details');
				if ($result->num_rows() == 1)
				{
					list($entity) = $result->fetch_row();
					$result->free();
				}
				else
					return false;
		
				if (!($entity = @unserialize($entity)))
					return false;

				$matches = array();
                if (preg_match('/([a-zA-Z0-9-_]+): ?([^;]*) ?/', $attributes['constraint'], $matches))
                {
                    $constraint_type = strtolower($matches[1]);
                    $constraint_args = explode('|', strtolower($matches[2]));
                }
                else
                	return false;

				if (!isset($entity->contents) || !isset($entity->contents[$attributes['content']]) || !isset($entity->constraints) || !isset($entity->constraints[$constraint_type]))
					return false;

				$content = $entity->contents[$attributes['content']];
				$constraint = $entity->constraints[$constraint_type];
				
        		// create ucm_object for selected module
        		$ucm = new ub_ucm($attributes['module']);
        		if (!$ucm)
        			return false;

				// loop until we reached the right content type
                $arg_index = 0;
				while (($content_type = $ucm->get_next_content_type()) != $content->type)
				{
                    foreach ($constraint->constraints as $cur_constraint)
                    {
    					if (isset($cur_constraint->content_type) && $cur_constraint->content_type == $content_type)
    					{
							switch($cur_constraint->type)
							{
								case CONSTRAINT_PRIMARY:
									if (isset($cur_constraint->argument) && isset($cur_constraint->argument->type) && isset($constraint_args[$arg_index]))
									{
										$cur_constraint->{$cur_constraint->argument->type} = $constraint_args[$arg_index];
										$arg_index++;
									}
										
									if (isset($cur_constraint->ident) && isset($cur_constraint->value) && isset($cur_constraint->operator) && isset($cur_constraint->glue) && isset($cur_constraint->quotes))
									{
										if (isset($cur_constraint->function) && isset($cur_constraint->function->code))
										{
											if (isset($cur_constraint->{$cur_constraint->argument->type}))
												$cur_constraint->value = preg_replace('/\$\$content/', $cur_constraint->{$cur_constraint->argument->type}, $cur_constraint->function->code);
										}
										$ucm->set_constraint($cur_constraint->ident, $cur_constraint->value, $cur_constraint->operator, $cur_constraint->glue, $cur_constraint->quotes);
									}
									break;

								case CONSTRAINT_ORDER:
									if (isset($cur_constraint->argument) && isset($cur_constraint->argument->type) && isset($constraint_args[$arg_index]))
									{
										$cur_constraint->{$cur_constraint->argument->type} = $constraint_args[$arg_index];
										$arg_index++;
									}
										
									if (isset($cur_constraint->ident) && isset($cur_constraint->order))
										$ucm->set_order($cur_constraint->ident, $cur_constraint->order);
									break;
							}
						}
					}

                    if (isset($constraint->limit) && isset($constraint->limit->content_type) && $constraint->limit->content_type == $content_type)
                    {
                        if (isset($constraint->limit->argument) && isset($constraint->limit->argument->type) && isset($constraint_args[$arg_index]))
                            $constraint->limit->{$constraint->limit->argument->type} = $constraint_args[$arg_index];
                            
                        if (isset($constraint->limit->length) && isset($constraint->limit->offset))
                            $ucm->set_limit($constraint->limit->offset, $constraint->limit->length);
                    }

					// get content via ucm-layer using the above set constraints
					$collection = $ucm->get_content();
					if ($collection->count() != 1)
						return false;
						
					$ucm = $collection->get_content();
                    $ucm = reset($ucm);
				}
				
				// check if content is language_dependent
				if ($content->language_dependent)
				{
					// get lang ident
					$lang_ident = (isset($this->unibox->session->env->replace->lang_ident)) ? $this->unibox->session->env->replace->lang_ident : $this->unibox->session->lang_ident;
					$ucm = $ucm->get_content_by_id($lang_ident);
				}
				else
				{
					foreach ($constraint->constraints as $cur_constraint)
					{
                        if ($cur_constraint->content_type == $content_type)
                        {
							switch($cur_constraint->type)
							{
								case CONSTRAINT_PRIMARY:
									if (isset($cur_constraint->argument) && isset($cur_constraint->argument->type) && isset($constraint_args[$arg_index]))
									{
										$cur_constraint->{$cur_constraint->argument->type} = $constraint_args[$arg_index];
										$arg_index++;
									}
										
									if (isset($cur_constraint->ident) && isset($cur_constraint->value) && isset($cur_constraint->operator) && isset($cur_constraint->glue) && isset($cur_constraint->quotes))
									{
										if (isset($cur_constraint->function) && isset($cur_constraint->function->code))
										{
											if (isset($cur_constraint->{$cur_constraint->argument->type}))
												$cur_constraint->value = preg_replace('/\$\$content/', $cur_constraint->{$cur_constraint->argument->type}, $cur_constraint->function->code);
										}
										$ucm->set_constraint($cur_constraint->ident, $cur_constraint->value, $cur_constraint->operator, $cur_constraint->glue, $cur_constraint->quotes);
									}
									break;

								case CONSTRAINT_ORDER:
									if (isset($cur_constraint->argument) && isset($cur_constraint->argument->type) && isset($constraint_args[$arg_index]))
									{
										$cur_constraint->{$cur_constraint->argument->type} = $constraint_args[$arg_index];
										$arg_index++;
									}
										
									if (isset($cur_constraint->ident) && isset($cur_constraint->order))
										$ucm->set_order($cur_constraint->ident, $cur_constraint->order);
									break;
							}
						}
                    }
						
					if (isset($constraint->limit) && isset($constraint->limit->content_type) && $constraint->limit->content_type == $content_type)
					{
						if (isset($constraint->limit->argument) && isset($constraint->limit->argument->type) && isset($constraint_args[$arg_index]))
							$constraint->limit->{$constraint->limit->argument->type} = $constraint_args[$arg_index];
							
						if (isset($constraint->limit->length) && isset($constraint->limit->offset))
							$ucm->set_limit($constraint->limit->offset, $constraint->limit->length);
					}
					
					if (isset($content->function))
						if (isset($content->function_fields_only))
							$collection = $ucm->get_content(null, array($content->field => $content->function), $content->function_fields_only);
						else
							$collection = $ucm->get_content(null, array($content->field => $content->function));
					else
						$collection = $ucm->get_content();
					if ($collection->count() != 1)
						return false;
						
					$ucm = $collection->get_content();
                    $ucm = reset($ucm);
				}
					
				// get requested data from content
				if (!($data = $ucm->get_data_by_id($content->field)))
					return false;

				// process formatting
                if (isset($attributes['formatting']) && isset($entity->formatting) && is_array($entity->formatting) && preg_match_all('/([a-zA-Z0-9-_]+): ?([^;]*) ?/', $attributes['formatting'], $matches, PREG_SET_ORDER))
                {
					foreach ($matches as $formatting)
					{
						$formatting_type = $formatting[1];
						$formatting_args = explode('|', strtolower($formatting[2]));
						
						if (isset($entity->formatting[$formatting_type]))
						{
							$arg_index = 0;
							if (isset($entity->formatting[$formatting_type]->arguments) && is_array($entity->formatting[$formatting_type]->arguments))
							{
								$arguments = array();
								foreach ($entity->formatting[$formatting_type]->arguments as $argument)
								{
									if (isset($argument->ident))
									{
										if ($argument->ident == '__content')
											$arguments[] = $data;
										elseif (isset($formatting_args[$arg_index]))
										{
											$arguments[] = $formatting_args[$arg_index];
											$arg_index++;
										}
									}
								}
								// if all arguments we're found, replace
								if (count($arguments) == count($entity->formatting[$formatting_type]->arguments))
									$entity->formatting[$formatting_type]->code = preg_replace('/\$\$(\d+)/e', '$arguments[\\1-1]', $entity->formatting[$formatting_type]->code);
							}
							
							// evaluate formatting code;
							eval('$data = '.$entity->formatting[$formatting_type]->code.';');
						}
					}
                }
				return $data;
    		}
    	}
    	else
    		return false;
    } // end ubc_process_replacement()

    /**
    * removes all ub-code tags
    * 
    * @param        $text               text to search for ubc-tags (string)
    * @return       text with tags removed (string)
    */
    public function strip_ubc($text)
    {
        $return = '';

        $tok = $this->get_token('[', $text);
        while ($tok !== false)
        {
            $return .= $tok;
            $str = $this->get_token(']');
            $tag = trim($str);
            if ($tag[0] == '/')
                $tag = strtolower(strtok(substr($str, 1), ' '));
            else
                $tag = strtolower(strtok($str, ' '));
            
            if (!in_array($tag, $this->ubc_tags))
                $return .= '['.$str.']';
                
            $tok = $this->get_token('[');
        }
        return $return;
    } // end strip_ubc()

    #################################################################################################
    ### html functions
    #################################################################################################

    /**
    * transforms html-code to ub-code
    * 
    * @param        $text               html source to be parsed to ubc (string)
    * @return       ubc coded text (string)
    */
    public function html2ubc($text)
    {
        // clear stacks etc.
        $this->ubc_stack = array();
        $this->html_stack = array();
        $this->ubc_text = '';

        // prevent &lt; and &gt; from being decoded
        $unique_mark = md5(microtime());
        $text = preg_replace('/&(gt|lt);/ie', '\'&\'.\$unique_mark.\'-\\1;\'', $text);

        $text = stripslashes(html_entity_decode($text));
        $tok = $this->get_token('<', $text);
        while ($tok !== false)
        {
            $this->ubc_text .= $tok;
            $this->html_process_tag($this->get_token('>'));
            $tok = $this->get_token('<');
        }

        // close all remaining tags
        for ($i = count($this->ubc_stack); $i < 0; $i--)
            $this->ubc_text .= '[/'.$this->ubc_stack[$i].']';

        // now decode saved &lt; and &gt;
        $this->ubc_text = preg_replace('/&'.$unique_mark.'-(gt|lt);/ie', 'html_entity_decode(\'&\\1;\')', $this->ubc_text);
        $this->ubc_text = str_replace('\r\n', "\r\n", $this->ubc_text);

        // combine multiple spaces
        $this->ubc_text = preg_replace('/\040+/', ' ', $this->ubc_text);

        // process smileys
        $sql_string  = 'SELECT
                          id,
                          code
                        FROM
                          sys_smileys';
        $result = $this->unibox->db->query($sql_string, 'failed to select smileys');
        if ($result->num_rows() > 0)
        {
            $patterns = $replacements = array();
            while (list($id, $code) = $result->fetch_row())
            {
                $patterns[] = '/'.preg_quote($code, '/').'/';
                $replacements[] = '[smiley id="'.$id.'" /]';
            }
            $result->free();
            $this->ubc_text = preg_replace($patterns, $replacements, $this->ubc_text);
        }

        // return transformated text
        return trim($this->ubc_text);
    } // end html2ubc()

	protected function html_dissect_tag($str)
	{
        $str = stripslashes(trim($str));
        if ($str == '')
            return false;
            
		$tag = new stdClass();
		
        // decide what tag type to process
        if ($str[0] == '/')
        {
            $tag->type = TAG_CLOSING;
            $tag->tag = strtolower(strtok(substr($str, 1), ' '));
        }
        elseif ($str[strlen($str)-1] == '/')
        {
            $tag->type = TAG_SELFCONTAINED;
            $tag->tag = strtolower(strtok($str, ' '));
        }
        else
        {   
            $tag->type = TAG_OPENING;
            $tag->tag = strtolower(strtok($str, ' '));
        }
        
        if ($tag->type != TAG_CLOSING)
        {
            // match attributes
            $matches = array();
            preg_match_all('/([a-zA-Z]+)=((\")|(\'))(.*?)(?(3)\"|\') ?/', strtok(''), $matches);

            // build attributes array
            if (count($matches[0]) != 0)
                $tag->attributes = array_combine(array_map('strtolower', $matches[1]), array_map('stripslashes', $matches[5]));
            else
                $tag->attributes = array();
			
            // parse style if set
            if (isset($tag->attributes['style']))
            {
	            $matches = array();
                preg_match_all('/([a-zA-Z-]+): ?([^;]*) ?/', $tag->attributes['style'], $matches);
                if (count($matches[0]) != 0)
                	$tag->styles = array_combine(array_map('strtolower', $matches[1]), array_map('strtolower', $matches[2]));
                else
                	$tag->styles = array();
				
                unset($tag->attributes['style']);
            }
            else
            	$tag->styles = array();
        }
        
        return $tag;
	}

    /**
    * adds a ubc tag as html replacement
    * 
    * @param        $html_tag           html tag to be added (string)
    * @param        $tag                ubc tag to be added (string)
    * @param        $tag_type           what kind of tag - opening, closing, selfcontained (string)
    * @param        $attributes         ubc-attributes of the current tag (string)
    * @return       result (bool)
    */
    protected function add_ubc_tag($html_tag, $tag, $tag_type, $attributes = array())
    {
        if ($tag_type == TAG_CLOSING)
        {
            $this->ubc_text .= '[/'.$tag.']';
            return true;
        }
        else
        {
            // reject if missing a required attribute
            if (isset($this->ubc_req_attr[$tag]))
            {
                foreach ($this->ubc_req_attr[$tag] as $attr)
                    if (!isset($attributes[$attr]))
                        return false;
                
                // merge required and optional attributes
                if (isset($this->ubc_opt_attr[$tag]))
                    $allowed_attr = array_merge($this->ubc_req_attr[$tag], $this->ubc_opt_attr[$tag]);
                else
                    $allowed_attr = $this->ubc_req_attr[$tag];
            }
            elseif (isset($this->ubc_opt_attr[$tag]))
                $allowed_attr = $this->ubc_opt_attr[$tag];
            else
                $allowed_attr = array();

            // reject all not allowed attributes
            // bad workaround for php < 5.10
            $allowed_attr = array_flip($allowed_attr);
            if (function_exists('array_intersect_key'))
                array_intersect_key($attributes, $allowed_attr);
            else
            {
                $result = array();
                foreach ($attributes as $key => $value)
                    if (array_key_exists($key, $allowed_attr))
                        $result[$key] = $attributes[$key];
                $attributes = $result;
            }

            // add tag
            $this->ubc_text .= '['.$tag;
            foreach ($attributes as $attr => $attr_value)
                $this->ubc_text .= ' '.$attr.'="'.addslashes($attr_value).'"';
        }
        // close tag
        if ($tag_type == TAG_OPENING)
        {
            $this->ubc_stack[] = $tag;
            $this->html_stack[] = $html_tag;
            $this->ubc_text .= ']';
        }
        else
           $this->ubc_text .= ' /]';
        return true;
    } // end add_ubc_tag();
    
    /**
    * converts html-tags to ubc-tags (editor related)
    * 
    * @param        $str                html token to be processed to ubc (string)
    */
    protected function html_process_tag($str)
    {
		$tag = $this->html_dissect_tag($str);
		if ($tag === false)
			return;

        // check if we're in a code or fixed section or if we got an invalid tag
        if ((end($this->html_stack) == 'code' && strtolower($str) != '/code') || (end($this->html_stack) == 'fixed' && strtolower($str) != '/fixed') || !in_array($tag->tag, $this->html_tags))
        {
            $this->ubc_text .= '&lt;'.$str.'&gt;';
            return;
        }

        if ($tag->type != TAG_CLOSING)
        {
        	$matches = array();
            switch ($tag->tag)
            {
                // url, email, anchor
                case 'a':
                    if (isset($tag->attributes['href']))
                    {
                        if (preg_match('/(^mailto:)(.*)/i', $tag->attributes['href'], $matches))
                        {
                            $tag->attributes['href'] = $matches[2];
                            $this->add_ubc_tag($tag->tag, 'email', $tag->type, $tag->attributes);
                        }
                        elseif (preg_match('/^\[(replace [^\]]+)\]/i', $tag->attributes['href'], $matches))
                        {
                        	$tag->attributes['href'] = '$$'.$matches[1].'$$';
                        	$this->add_ubc_tag($tag->tag, 'url', $tag->type, $tag->attributes);
                        }
                        // TODO: ubc parse: FIX match protocol and add default one if not set
                        // preg_match("/^((.*):\/\/)?(.*)/i", $tag->attributes['href'], $matches);
                        else
                            $this->add_ubc_tag($tag->tag, 'url', $tag->type, $tag->attributes);
                    }
                    elseif (isset($tag->attributes['name']))
                    {
                        $tag->type = TAG_SELFCONTAINED;
                        $this->add_ubc_tag($tag->tag, 'anchor', $tag->type, $tag->attributes);
                    }
                    break;
                case 'sup':
                    $this->add_ubc_tag($tag->tag, $tag->tag, $tag->type);
                    break;
                case 'sub':
                    $this->add_ubc_tag($tag->tag, $tag->tag, $tag->type);
                    break;
                // list listtype=ul/empty
                case 'ul':
                    $tag->attributes['listtype'] = 'ul';
                    if (isset($tag->styles['list-style-type']))
                    {
                        $tag->attributes['vistype'] = $tag->styles['list-style-type'];
                        unset($tag->styles['list-style-type']);
                    }
                    $this->add_ubc_tag($tag->tag, 'list', $tag->type, $tag->attributes);
                    break;
                // list listtype=ol
                case 'ol':
                    $tag->attributes['listtype'] = 'ol';
                    if (isset($tag->styles['list-style-type']))
                    {
                        $tag->attributes['vistype'] = $tag->styles['list-style-type'];
                        unset($tag->styles['list-style-type']);
                    }
                    $this->add_ubc_tag($tag->tag, 'list', $tag->type, $tag->attributes);
                    break;
                // listentry
                case 'li':
                    $this->add_ubc_tag($tag->tag, 'listentry', $tag->type);
                    break;
                // strong
                case 'strong':
                    $this->add_ubc_tag($tag->tag, $tag->tag, $tag->type);
                    break;
                // italic
                case 'em':
                    $this->add_ubc_tag($tag->tag, 'italic', $tag->type);
                    break;
                // underline
                case 'u':
                    $this->add_ubc_tag($tag->tag, 'underline', $tag->type);
                    break;
                // strikethrough
                case 'strike':
                    $this->add_ubc_tag($tag->tag, $tag->tag, $tag->type);
                    break;
                // abbr
                case 'acronym':
                    $this->add_ubc_tag($tag->tag, 'abbr', $tag->type, $tag->attributes);
                    break;
                // align        
                case 'div':
                case 'p':
                    if (isset($tag->styles['text-align']))
                    {
                        $tag->attributes['value'] = $tag->styles['text-align'];
                        unset($tag->styles['text-align']);
                        $this->add_ubc_tag($tag->tag, 'align', $tag->type, $tag->attributes);
                    }
                    break;
                case 'font':
                    if (isset($tag->attributes['color']))
                    {
                        $tag->attributes['value'] = $tag->attributes['color'];
                        $this->add_ubc_tag($tag->tag, 'color', $tag->type, $tag->attributes);
                    }
                    elseif (isset($tag->attributes['size']))
                    {
                        $tag->attributes['value'] = 1 + ($tag->attributes['size'] - 3) * 0.2.'em';
                        $this->add_ubc_tag($tag->tag, 'size', $tag->type, $tag->attributes);
                    }
                    break;
                // special case: span
                case 'span':
                    if (isset($tag->attributes['lang']))
                        $this->add_ubc_tag($tag->tag, 'lang', $tag->type, $tag->attributes);
                    break;
                case 'br':
                    $this->add_ubc_tag($tag->tag, 'br', $tag->type);
                    break;
                case 'hr':
                    $this->add_ubc_tag($tag->tag, 'separator', $tag->type);
                    break;
                case 'img':
                    if (isset($tag->attributes['themes']))
                    {
                    	$theme_idents = array();
                    	$sql_string  = 'SELECT
										  a.theme_ident,
										  b.subtheme_ident
										FROM sys_themes AS a
										  LEFT JOIN sys_subthemes AS b
											ON b.theme_ident = a.theme_ident
										ORDER BY
										  a.theme_ident ASC';
						$result = $this->unibox->db->query($sql_string, 'failed to select theme idents');
						if ($result->num_rows() > 0)
						{
							while (list($theme_ident, $subtheme_ident) = $result->fetch_row())
								$theme_idents[$theme_ident.'_'.$subtheme_ident] = true;
							$result->free();
						}
						
	                    preg_match_all('/([a-z_]+): ?(\d+)\|(\d+)\|(\d+); ?/', $tag->attributes['themes'], $matches, PREG_SET_ORDER);

						$tag->attributes['themes'] = '';
	                	foreach ($matches as $theme)
	                	{
	                		$theme_ident = strtolower($theme[1]);
	                		if (isset($theme_idents[$theme_ident]))
	                    		$tag->attributes['themes'] .= $theme_ident.': '.$theme[2].'|'.$theme[3].'|'.$theme[4].'; ';
	                	}
                    }
                    unset($tag->attributes['src']);
                    unset($tag->attributes['border']);
                    unset($tag->attributes['width']);
                    unset($tag->styles['width']);
                    unset($tag->attributes['height']);
                    unset($tag->styles['height']);
                    if (isset($tag->styles['float']))
                    {
                        $tag->attributes['float'] = $tag->styles['float'];
                        unset($tag->styles['float']);
                    }
                    if (!isset($tag->attributes['zoom']))
                        $tag->attributes['zoom'] = 0;

                    if (isset($tag->styles['margin']) && preg_match_all('/\d+/', $tag->styles['margin'], $matches))
                    {
                        if (count($matches[0]) == 2)
                        {
                            $tag->attributes['margin-left'] = $tag->attributes['margin-right'] = $matches[0][1];
                            $tag->attributes['margin-top'] = $tag->attributes['margin-bottom'] = $matches[0][0];
                        }
                        elseif (count($matches[0]) == 3)
                        {
                            $tag->attributes['margin-left'] = $tag->attributes['margin-right'] = $matches[0][1];
                            $tag->attributes['margin-top'] = $matches[0][0];
                            $tag->attributes['margin-bottom'] = $matches[0][2];
                        }
                        elseif (count($matches[0]) == 4)
                        {
                            $tag->attributes['margin-left'] = $matches[0][3];
                            $tag->attributes['margin-right'] = $matches[0][1];
                            $tag->attributes['margin-top'] = $matches[0][0];
                            $tag->attributes['margin-bottom'] = $matches[0][2];
                        }
                        else
                            $tag->attributes['margin-left'] = $tag->attributes['margin-right'] = $tag->attributes['margin-top'] = $tag->attributes['margin-bottom'] = $matches[0][0];
                        unset($tag->styles['margin']);
                    }
                    else
                    {
                        if (isset($tag->styles['margin-left']) && preg_match('/\d+/', $tag->styles['margin-left'], $matches))
                        {
                            $tag->attributes['margin-left'] = $matches[0];
                            unset($tag->styles['margin-left']);
                        }
                        if (isset($tag->styles['margin-right']) && preg_match('/\d+/', $tag->styles['margin-right'], $matches))
                        {
                            $tag->attributes['margin-right'] = $matches[0];
                            unset($tag->styles['margin-right']);
                        }
                        if (isset($tag->styles['margin-top']) && preg_match('/\d+/', $tag->styles['margin-top'], $matches))
                        {
                            $tag->attributes['margin-top'] = $matches[0];
                            unset($tag->styles['margin-top']);
                        }
                        if (isset($tag->styles['margin-bottom']) && preg_match('/\d+/', $tag->styles['margin-bottom'], $matches))
                        {
                            $tag->attributes['margin-bottom'] = $matches[0];
                            unset($tag->styles['margin-bottom']);
                        }
                    }
                    $this->add_ubc_tag($tag->tag, 'img', $tag->type, $tag->attributes);
                    break;
                case 'table':
                    // process style information
                    // text-align, border-width, width, height, border-color, background-color, background-image
                    if (isset($tag->styles['text-align']))
                    {
                        $tag->attributes['text-align'] = $tag->styles['text-align'];
                        unset($tag->styles['text-align']);
                    }
                    if (isset($tag->styles['width']))
                    {
                        $tag->attributes['width'] = $tag->styles['width'];
                        unset($tag->styles['width']);
                    }
                    if (isset($tag->styles['height']))
                    {
                        $tag->attributes['height'] = $tag->styles['height'];
                        unset($tag->styles['height']);
                    }
                    if (isset($tag->styles['background-color']))
                    {
                        if (stristr($tag->styles['background-color'], 'rgb'))
                        {
                            preg_match('/\((\d{1,3}), *(\d{1,3}), *(\d{1,3})\)/', $tag->styles['background-color'], $matches);
                            $tag->styles['background-color'] = '#';
                            for ($i=1; $i<=3; $i++)
                            {
                                $hex = dechex($matches[$i]);
                                if (strlen($hex) < 2)
                                    $tag->styles['background-color'].= '0'.$hex;
                                else
                                    $tag->styles['background-color'].= $hex;
                            }
                        }
                        $tag->attributes['background-color'] = $tag->styles['background-color'];
                        unset($tag->styles['background-color']);
                    }
                    $this->add_ubc_tag($tag->tag, 'table', $tag->type, $tag->attributes);
                    break;
                case 'thead':
                    $this->add_ubc_tag($tag->tag, 'table_head', $tag->type, $tag->attributes);
                    break;
                case 'tbody':
                    $this->add_ubc_tag($tag->tag, 'table_body', $tag->type, $tag->attributes);
                    break;
                case 'tfoot':
                    $this->add_ubc_tag($tag->tag, 'table_foot', $tag->type, $tag->attributes);
                    break;
                case 'tr':
                case 'th':
                case 'td':
                    if (isset($tag->styles['text-align']))
                    {
                        $tag->attributes['text-align'] = $tag->styles['text-align'];
                        unset($tag->styles['text-align']);
                    }
                    if (isset($tag->styles['vertical-align']))
                    {
                        $tag->attributes['vertical-align'] = $tag->styles['vertical-align'];
                        unset($tag->styles['vertical-align']);
                    }
                    if (isset($tag->styles['height']))
                    {
                        $tag->attributes['height'] = $tag->styles['height'];
                        unset($tag->styles['height']);
                    }
                    // column only
                    if (isset($tag->styles['width']) && $tag->tag == 'td')
                    {
                        $tag->attributes['width'] = $tag->styles['width'];
                        unset($tag->styles['width']);
                    }
                    if (isset($tag->styles['background-color']))
                    {
                        if (stristr($tag->styles['background-color'], 'rgb'))
                        {
                            preg_match('/\((\d{1,3}), *(\d{1,3}), *(\d{1,3})\)/', $tag->styles['background-color'], $matches);
                            $tag->styles['background-color'] = '#';
                            for ($i=1; $i<=3; $i++)
                            {
                                $hex = dechex($matches[$i]);
                                if (strlen($hex) < 2)
                                    $tag->styles['background-color'].= '0'.$hex;
                                else
                                    $tag->styles['background-color'].= $hex;
                            }
                        }
                        $tag->attributes['background-color'] = $tag->styles['background-color'];
                        unset($tag->styles['background-color']);
                    }
                    if ($tag->tag == 'th')
                        $this->add_ubc_tag($tag->tag, 'table_cell_head', $tag->type, $tag->attributes);
                    elseif ($tag->tag == 'tr')
                        $this->add_ubc_tag($tag->tag, 'table_row', $tag->type, $tag->attributes);
                    elseif ($tag->tag == 'td')
                        $this->add_ubc_tag($tag->tag, 'table_cell', $tag->type, $tag->attributes);
                    break;
                case 'embed':
                    $this->add_ubc_tag($tag->tag, 'flash', TAG_SELFCONTAINED, $tag->attributes);
                    break;
            }

            // process styles
            unset($tag->attributes);
            foreach ($tag->styles as $key => $value)
            {
                // switch style type
                switch ($key)
                {
                    case 'color':
                        // process to hex if passed in rgb
                        if (stristr($value, 'rgb'))
                        {
                            preg_match('/\((\d{1,3}), *(\d{1,3}), *(\d{1,3})\)/', $value, $matches);
                            $value = '#';
                            for ($i=1; $i<=3; $i++)
                            {
                                $hex = dechex($matches[$i]);
                                if (strlen($hex) < 2)
                                    $value.= '0'.$hex;
                                else
                                    $value.= $hex;
                            }
                        }
                        $tag->attributes['value'] = $value;
                        $this->add_ubc_tag($tag->tag, 'color', TAG_OPENING, $tag->attributes);
                        break;
                    case 'font-style':
                        if ($value == 'italic')
                            $this->add_ubc_tag($tag->tag, 'italic', TAG_OPENING);
                        break;
                    case 'font-weight':
                        if ($value == 'bold')
                            $this->add_ubc_tag($tag->tag, 'strong', TAG_OPENING);
                        break;
                    case 'text-align':
                        $tag->attributes['value'] = $value;
                        $this->add_ubc_tag($tag->tag, 'align ', TAG_OPENING);
                        break;
                }
            }
        }
        // closing tag
        else
        {
            // if last opened tag is not the same as the current one - close until current one
            if (end($this->html_stack) != $tag->tag)
            {
                // do nothing if current (closing) tag has never been opened
                if (!in_array($tag->tag, $this->html_stack))
                    return;
                
                $len = 0;
                $rev_stack = array_reverse($this->html_stack);
                foreach ($rev_stack AS $cl_tag)
                {
                    $len--;
                    // add closing tag
                    $this->add_ubc_tag($cl_tag, $this->ubc_stack[count($this->ubc_stack)+$len], $tag->type);
                    if ($cl_tag == $tag->tag)
                    	break;
                }
                $this->html_stack = array_slice($this->html_stack, 0, $len);
                $this->ubc_stack = array_slice($this->ubc_stack, 0, $len);
            }
            else
            {
                $this->add_ubc_tag($tag->tag, $this->ubc_stack[count($this->ubc_stack)-1], $tag->type);
                $this->html_stack = array_slice($this->html_stack, 0, -1);
                $this->ubc_stack = array_slice($this->ubc_stack, 0, -1);
            }
        }
    } // end html_process_tag()
    
    public function set_replacement($ident, $value)
    {
    	$this->unibox->session->env->replace->$ident = $value;
    }

}

?>
