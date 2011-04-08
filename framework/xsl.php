<?php

/**
*
* uniBox 2.0 (winterspring)\n
* (c) Media Soma GbR
*
* 0.1       02.03.2005  jn      1st release\n
* 0.11      17.03.2005  jn      minor improvements - fixed comments\n
* 0.12      06.04.2005  jn      changed way of template loading in process()\n
* 0.13      14.07.2005  jn      removed unnecessary brackets and added some comments
*
*/

class ub_xsl
{
    /**
    * class version
    * 
    */
    const version = '0.1.0';

    /**
    * xslt processing time
    *
    */
    protected $processtime = 0;

    /**
    * xslt stylesheet(s) as DOMDocument object
    *
    */
    protected $stylesheet = null;

	protected $processed_templates = array();

    /**
    * returns class version
    * 
    * @return       version-number (float)
    */
    public static function get_version()
    {
        return ub_xsl::version;
    }

    /**
    * processtime read method
    * 
    * @return       processtime in sec (string)
    */
    public function get_processtime()
    {
        return $this->processtime;
    } // end ub_xsl->get_processtime()

    /**
    * xslt processing
    *
    * @param        $xml                xml tree (DOMDocument object)
    * @param        $return_html        return html as string or as DOMDocument (bool)
    * @param        $format             format output (bool)
    * @return       processed content (mixed)
    */
    public function process($xml)
    {
        // begin time measurement
        $xsl_starttime = microtime(true);

        // get framework instance
        $this->unibox = ub_unibox::get_instance();

		// process templates
		$this->processed_templates = array();
		$this->process_templates();

        // get templates
        $templates_extension = $this->unibox->get_templates_extension();
        $templates_extension_loaded = array();
        $templates_content = $this->unibox->get_templates_content();
        $templates_content_loaded = array();
        $templates_loaded = array();

        // prepare empty stylesheet
        $this->stylesheet = new DOMDocument('1.0', 'UTF-8');
        $this->stylesheet->loadXML('<xsl:stylesheet version="1.0" xmlns="http://www.w3.org/1999/xhtml" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:ubc="http://www.media-soma.de/ubc" xmlns:php="http://php.net/xsl"></xsl:stylesheet>');

		// insert default xsl:output node
		$node = $this->stylesheet->createElementNS('http://www.w3.org/1999/XSL/Transform', 'xsl:output');
		$node->setAttribute('method', 'xml');
		$node->setAttribute('encoding', 'UTF-8');
		$node->setAttribute('indent', 'yes');
		$node->setAttribute('omit-xml-declaration', 'yes');
		$node->setAttribute('doctype-system', 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd');
		$node->setAttribute('doctype-public', '-//W3C//DTD XHTML 1.0 Strict//EN');
		$this->stylesheet->documentElement->appendChild($node);

		// add empty content template
		$node = $this->stylesheet->createElementNS('http://www.w3.org/1999/XSL/Transform', 'xsl:template');
		$node->setAttribute('match', 'content');
		$this->stylesheet->documentElement->appendChild($node);

        $xml->goto('unibox');

		// loop over all templates
        foreach ($this->processed_templates as $template_ident => $template_info)
        {
        	$file = $template_info['path'].$template_info['file'];
        	$path = $template_info['path'];

            $xml->add_node('theme_base');
            $xml->set_attribute('template', $template_ident);
            $xml->add_value('path', $path);
            $xml->parse_node();

            // load template file
            $template_xml = new DOMDocument('1.0', 'UTF-8');
        	$template_xml->load($file);

            if ($nodelist = $template_xml->documentElement->getElementsByTagName('template'))
            {
            	for ($key = 0; $key < $nodelist->length; $key++)
            	{
            		// get template name
            		$template_node = $nodelist->item($key);

					// add template
					$this->stylesheet->documentElement->appendChild($this->stylesheet->importNode($template_node, true));
            	}
            }
        }
        $xml->restore();

        // debug tool
		if (DEBUG > 0)
		{
			file_put_contents('debug.xsl', $this->stylesheet->saveXML());
			file_put_contents('debug.xml', $this->unibox->xml->get_tree());
		}

        if ($xml instanceof ub_xml)
            $xml = $xml->get_object();
        elseif (!($xml instanceof DOMDocument))
            throw new ub_exception_runtime('xml is neither ub_xml nor DOMDocument');

        // start new processor
        $processor = new XSLTProcessor();

        // register php functions if any
        if (isset($this->unibox->config->system->xslt_php_functions) && $this->unibox->config->system->xslt_php_functions == 1)
            $processor->registerPHPFunctions();

        // process xslt
    	$processor->importStylesheet($this->stylesheet);
    	$xml = $processor->transformToXML($xml);

	    // remove invalid namespaces
	    $xml = str_replace(' xmlns:php="http://php.net/xsl"', '', $xml);
	    $xml = str_replace(' xmlns:ubc="http://www.media-soma.de/ubc"', '', $xml);
	    
        // end time measurement
        $xsl_endtime = microtime(true);
        $this->processtime = $xsl_endtime - $xsl_starttime;

        return $xml;
    } // end ub_xsl->process()

    /**
    * loads all required xsl-templates, their corresponding translations and styles
    * 
    */
    protected function process_templates()
    {
        $this->unibox->xml->goto('unibox');
        $templates = array();

        // check for template fallback
        $sql_string  = 'SELECT DISTINCT
                          a.template_ident,
                          a.module_ident,
                          a.template_filename,
                          b.filename_extension
                        FROM
                          sys_templates AS a,
                          sys_output_formats AS b
                        WHERE
                          a.template_ident IN (\''.implode('\', \'', $this->unibox->get_templates()).'\')
                          AND
                          b.output_format_ident = \''.$this->unibox->session->output_format_ident.'\'';
        $result = $this->unibox->db->query($sql_string, 'failed to retrieve translations for templates');
        if ($result->num_rows() > 0)
        {
	        while (list($template_ident, $module_ident, $template_filename, $file_extension) = $result->fetch_row())
	        	$templates[$template_ident] = $this->get_template_data($template_ident, $template_filename, $module_ident, $file_extension);
	        $result->free();

			// set templates array
        	$this->processed_templates = $templates;
        }

        // get styles
        $sql_string =  'SELECT DISTINCT
                          b.module_ident,
						  b.style_ident,
                          b.style_media,
                          b.style_filename
                        FROM
                          sys_template_styles AS a
							INNER JOIN sys_styles AS b
							  ON b.style_ident = a.style_ident
                        WHERE
                          a.template_ident IN (\''.implode('\', \'', $this->unibox->get_templates()).'\')';
        $result = $this->unibox->db->query($sql_string, 'failed to retrieve style information');

        $this->unibox->xml->add_node('styles');
        if ($result->num_rows() > 0)
        {
	        while (list($module_ident, $style_ident, $style_media, $style_filename) = $result->fetch_row())
	        {
	        	if ($path = $this->get_style_data($style_filename, $style_ident, $module_ident))
	        	{
		            $this->unibox->xml->add_node('style');
		            $this->unibox->xml->add_value('path', $path);
		            $this->unibox->xml->add_value('filename', $style_filename);
		            $this->unibox->xml->add_value('media', $style_media);
		            $this->unibox->xml->parse_node();
	        	}
	        }
	        $result->free();
        }

        $this->unibox->xml->restore();

        // load translations
        $where = '';
        foreach($templates AS $template_ident => $array)
        {
            if ($where != '')
                $where .= ' OR ';
            $where .= '(a.template_ident = \''.$template_ident.'\' AND a.theme_ident = \''.$array['theme'].'\')';
        }

        $sql_string =  'SELECT DISTINCT
                          a.string_ident,
                          b.string_value
                        FROM
                          sys_template_translations AS a
                            LEFT JOIN sys_translations AS b
                              ON
                              (
                              b.string_ident = a.string_ident
                              AND
                              b.lang_ident = \''.$this->unibox->session->lang_ident.'\'
                              )
                        WHERE
                          '.$where;
        $result = $this->unibox->db->query($sql_string, 'failed to retrieve translations for templates \''.$templates.'\'');
        $this->unibox->xml->goto('root');
        $this->unibox->xml->add_node('translations');
        while (list($string_ident, $string_value) = $result->fetch_row())
            $this->unibox->xml->add_value($string_ident, ($string_value === null) ? $this->unibox->translations->error : $string_value);
        $result->free();
        $this->unibox->xml->parse_node();
        $this->unibox->xml->restore();
    } // end process_tempates()

	protected function get_template_data($template_ident, $template_filename, $module_ident, $file_extension)
	{
		$data = array();

		// fill data
        $data['file'] = '/templates/'.$module_ident.'/'.$template_filename.'.'.$file_extension;
        $data['theme'] = $this->unibox->session->env->themes->current->theme_ident;
    	$data['subtheme'] = $this->unibox->session->env->themes->current->subtheme_ident;
        
        // path
        $data['path'] = DIR_THEMES.$data['theme'].'/'.$data['subtheme'].'/'.$this->unibox->session->output_format_ident;

        // use fallback if file not found
        if (is_readable($data['path'].$data['file']))
        	return $data;

		// fallback
    	if ($this->unibox->session->env->themes->use_fallback)
        {
        	// try default subtheme fallback
        	if ($data['subtheme'] != $this->unibox->session->env->themes->current->default_subtheme_ident)
        	{
        		$data['subtheme'] = $this->unibox->session->env->themes->current->default_subtheme_ident;
        		$data['path'] = DIR_THEMES.$data['theme'].'/'.$data['subtheme'].'/'.$this->unibox->session->output_format_ident;
        		if (is_readable($data['path'].$data['file']))
        			return $data;
        	}

			// try theme fallback with current subtheme
    		$data['theme'] = $this->unibox->session->env->themes->fallback->theme_ident;
			$data['subtheme'] = $this->unibox->session->env->themes->fallback->subtheme_ident;
    		$data['path'] = DIR_THEMES.$data['theme'].'/'.$data['subtheme'].'/'.$this->unibox->session->output_format_ident;
    		if (is_readable($data['path'].$data['file']))
    			return $data;

			// try theme fallback with default subtheme
    		$data['theme'] = $this->unibox->session->env->themes->fallback->theme_ident;
			$data['subtheme'] = $this->unibox->session->env->themes->fallback->default_subtheme_ident;
    		$data['path'] = DIR_THEMES.$data['theme'].'/'.$data['subtheme'].'/'.$this->unibox->session->output_format_ident;
    		if (is_readable($data['path'].$data['file']))
    			return $data;
        }
    	throw new ub_exception_runtime('failed to load template \''.$template_ident.'\'');
	}

	protected function get_style_data($style_filename, $style_ident, $module_ident)
	{
        // build style path
        $path = DIR_THEMES.$this->unibox->session->env->themes->current->theme_ident.'/'.$this->unibox->session->env->themes->current->subtheme_ident.'/'.$this->unibox->session->output_format_ident.'/styles/'.$module_ident.'/';

		if (is_readable($path.$style_filename.'.css'))
			return $path;
        
		if ($this->unibox->session->env->themes->use_fallback)
        {
        	// fall back to default subtheme
        	$path = DIR_THEMES.$this->unibox->session->env->themes->current->theme_ident.'/'.$this->unibox->session->env->themes->current->default_subtheme_ident.'/'.$this->unibox->session->output_format_ident.'/styles/'.$module_ident.'/';
			if (is_readable($path.$style_filename.'.css'))
				return $path;

			// fall back to default theme
			$path = DIR_THEMES.$this->unibox->session->env->themes->fallback->theme_ident.'/'.$this->unibox->session->env->themes->fallback->subtheme_ident.'/'.$this->unibox->session->output_format_ident.'/styles/'.$module_ident.'/';
			if (is_readable($path.$style_filename.'.css'))
				return $path;

			// fall back to default theme and default subtheme
			$path = DIR_THEMES.$this->unibox->session->env->themes->fallback->theme_ident.'/'.$this->unibox->session->env->themes->fallback->default_subtheme_ident.'/'.$this->unibox->session->output_format_ident.'/styles/'.$module_ident.'/';
			if (is_readable($path.$style_filename.'.css'))
				return $path;
        }
        return false;
	}

    /**
    * resets the xsl layer
    *
    */
    public function reset()
    {
        $this->stylesheet = null;
        $this->processtime = null;
    } // end ub_xsl->reset()

} // end class ub_xsl

?>