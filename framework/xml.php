<?php

/**
*
* uniBox 2.0 (winterspring)\n
* (c) Media Soma GbR
*
* 0.1       02.03.2005  pr      1st release\n
* 0.2       10.03.2005  jn      added create from file and save to file support\n
* 0.3       16.03.2005  pr      added translation support\n
* 0.31      17.03.2005  jn      changed some comments, fixed other ones\n
* 0.32      01.04.2005  pr      added argument replacement for translations\n
* 0.33      05.04.2005	pr		minor fixes\n
* 0.34      14.07.2005  jn      added ability to remove nodes by name\n
* 0.35      28.03.2006  jn      changed to support multiple gotos
*
*/

class ub_xml
{
    /**
    * class version
    * 
    */
    const version = '0.1.0';

    /**
    * DOMDocument object
    *
    */
    protected $doc;

    /**
    * current DOMNode object
    *
    */
    protected $node;

    /**
    * contains all markers
    *
    */
    protected $marker = array();

    /**
    * contains all nodes that require a translation
    *
    */
    protected $translation = array();

    /**
    * holds the current DOMNode object while inserting into another one
    *
    */
    protected $current_nodes = array();

    /**
    * returns class version
    * 
    * @return       version-number (float)
    */
    public static function get_version()
    {
        return ub_xml::version;
    }

    /**
    * class constructor
    * 
    * @param        $file           filename to create document from (string)
    */
    public function __construct($file = '')
    {
        // open new xml document
        $this->doc = new DOMDocument('1.0', 'utf-8');
        // try if given file exists and is readable
        // load it if true, create empty root node if not
        if (trim($file) != '' && file_exists($file) && is_readable($file))
        {
            $this->doc->load($file);
            $this->node = $this->doc->documentElement;
        }
        else
        {
            $node = $this->doc->createElement('root');
            $this->node = $this->doc->appendChild($node);
        }
        // set marker on root-node
		$this->set_marker('root');
    } // end ub_xml->__construct()

    /**
    * adds a node to the xml tree
    *
    * @param        $name           node name (string)
    */
    public function add_node($name)
    {
        $this->node = $this->node->appendChild($this->doc->createElement($name));
    } // end ub_xml->add_node()

    /**
    * import a single node from a foreign DOMDocument
    *
    * @param        $domnode			Node to be imported (DOMNode)
    * @param		$deep				import all child nodes (bool)
    */
	public function import_node($domnode, $deep = true)
	{
		$node = $this->doc->importNode($domnode, $deep);
		$this->node->appendChild($node);
	}

    /**
    * import text xml
    *
    * @param        $text           xml to be imported (string)
    */
	public function import_xml($text)
	{
		if (empty($text))
			return;

		$xml = new DOMDocument;
		if (!@$xml->loadXML($text))
			return;

		if ($nodelist = $xml->documentElement->childNodes)
		{
			for ($key = 0; $key < $nodelist->length; $key++)
        	{
				// get node
            	$node = $nodelist->item($key);

				// import node
				$this->node->appendChild($this->doc->importNode($node, true));
            }
		}
	}

    /**
    * adds a text containing node to the xml tree
    * 
    * @param        $name           node name (string)
    * @param        $value          text value (string)
    * @param        $translate      shall the content be translated? (bool)
    * @param        $trl_args       translation replacements (array of strings)
    */
    public function add_value($name, $value = null, $translate = false, $trl_args = array())
    {
        // add information to translation array if requested
        if ($translate)
        {
        	$translation = new stdClass;
        	$translation->type = XML_VALUE;
        	$translation->node = $this->node->appendChild($this->doc->createElement($name));
        	$translation->args = $trl_args;
        	$this->translation[$value][] = $translation;

			/*
        	$this->translation[$value][] = new stdClass;
        	end($this->translation[$value]);
        	$this->translation[$value][key($this->translation[$value])]->type = XML_VALUE;
        	$this->translation[$value][key($this->translation[$value])]->node = $this->node->appendChild($this->doc->createElement($name));
            $this->translation[$value][key($this->translation[$value])]->args = $trl_args;
            */
        }
        else
        {
            // save current node
            $node = $this->node;
            
            // add value
            if ($value !== null)
            {
            	$value = preg_replace('/(&(?!#))/', '&#38;', $value);
            	$this->node = $this->node->appendChild($this->doc->createElement($name, $value));
            }
            else
            {
	            $this->node = $this->node->appendChild($this->doc->createElement($name));
				$this->set_attribute('null', 'true');
            }
        	$this->set_marker('last_value');
			        	
        	// restore previous node
        	$this->node = $node;
        }
    } // end ub_xml->add_value()

    /**
    * adds a text node to the xml tree
    *
    * @param        $name           node name (string)
    */
    public function add_text($value, $translate = false, $trl_args = array())
    {
        // add information to translation array if requested
        if ($translate)
        {
        	$translation = new stdClass;
        	$translation->type = XML_TEXT;
        	$translation->node = $this->node->appendChild($this->doc->createTextNode($value));
        	$translation->args = $trl_args;
        	$this->translation[$value][] = $translation;
        }
        else
	        $this->node->appendChild($this->doc->createTextNode($value));
    } // end ub_xml->add_text()

    /**
    * closes the current node and moves one node up in the xml tree
    *
    */
    public function parse_node()
    {
        // set marker everytime to the last added child
        $this->set_marker('last_child');
        // close current node (go back to it's parent node)
        if ($this->get_tagname() != 'root')
            $this->node = $this->node->parentNode;
    } // end ub_xml->parse_node()

    /**
    * adds an attribute to the current node
    * 
    * @param        $name           attibute name (string)
    * @param        $value          text value (string)
    * @param        $translate      shall the content be translated? (bool)
    * @param        $trl_args       translation replacements (array of strings)
    */
    public function set_attribute($name, $value, $translate = false, $trl_args = array())
    {
        // add information to translation array if requested
        if ($translate)
        {
        	$translation = new stdClass;
        	$translation->type = XML_ATTRIBUTE;
        	$translation->name = $name;
        	$translation->node = $this->node;
        	$translation->args = $trl_args;
        	$this->translation[$value][] = $translation;
        }
        else
        	$this->node->setAttribute($name, $value);
    } // end ub_xml->set_attribute()

    /**
    * activates the given marker
    * 
    * @param        $name           marker name (string)
    */
    public function goto($name)
    {
        // try if marker is set
        // set remembered node to current one if true, throw error if false
        if (isset($this->marker[$name]))
        {
            $this->current_nodes[] = $this->node;
            $this->node = $this->marker[$name];
        }
        else
        {
            $unibox = ub_unibox::get_instance();
            throw new ub_exception_runtime('could not jump to marker \''.$name.'\'');
        }
    } // end ub_xml->goto()

    /**
    * restore original node
    *
    */
    public function restore()
    {
        if (count($this->current_nodes) == 0)
        {
            $unibox = ub_unibox::get_instance();
            throw new ub_exception_runtime('no markers found to restore');
        }
        $this->node = array_pop($this->current_nodes);
    } // end ub_xml->restore()

    /**
    * sets a new marker
    * 
    * @param        $name           marker name (string)
    */
    public function set_marker($name = null)
    {
        if ($name !== null)
            $this->marker[$name] = $this->node;
        else
        {
            $unibox = ub_unibox::get_instance();
            throw new ub_exception_runtime('no name given for marker');
        }
    } // end ub_xml->set_marker()

    public function strip_goto($count = 1)
    {
        for ($i = 0; $i < $count; $i++)
            array_pop($this->current_nodes);
    }

    /**
    * checks if a marker exists
    * 
    * @param        $name           marker name (string)
    */
	public function isset_marker($name)
	{
		return (isset($this->marker[$name]));
	}

	/**
	* insert all nodes and attributes that have been saved for translation
	* 
	*/
	protected function translate()
    {
        $unibox = ub_unibox::get_instance();
		// do nothing if no translations are required
		if (count($this->translation) == 0)
			return;
		// save current node
		$node = $this->node;

		// get all translations from the database
		foreach ($this->translation as $string_ident => $obj)
			$translations[] = $unibox->db->cleanup($string_ident);

		$sql_string  = 'SELECT
						  string_ident,
						  string_value
						FROM
						  sys_translations
						WHERE
						  string_ident IN (\''.implode('\', \'', $translations).'\')
						  AND
						  lang_ident = \''.$unibox->session->lang_ident.'\'';
		$result = $unibox->db->query($sql_string, 'error while getting translations');
		while (list($string_ident, $string_value) = $result->fetch_row())
		{
			if (isset($this->translation[$string_ident]))
				// loop through all objects that require this translation
				foreach ($this->translation[$string_ident] as $obj)
				{
					// replace placeholders in string_value
	                if (count($obj->args) > 0)
	                    $string_value_replaced = preg_replace('/\$\$(\d+)/e', '$obj->args[\\1-1]', $string_value);
	                else
	                    $string_value_replaced = $string_value;
	
	                $this->node = $obj->node;
	                // add translation to appropriate xml type
					if ($obj->type == XML_VALUE)
						@$this->node->appendChild($this->doc->createTextNode($string_value_replaced));
					elseif ($obj->type == XML_ATTRIBUTE)
						@$this->set_attribute($obj->name, $string_value_replaced);
					elseif ($obj->type == XML_TEXT)
						@$this->node->nodeValue = $string_value_replaced;
				}
			unset($this->translation[$string_ident]);
		}
		$result->free();

        // check if all translations were found
		if (count($this->translation) > 0)
		{
            // FIX: show translation error on production systems?
			$string_value = $unibox->config->system->translation_error_message;

			// set all translations that were not found
			foreach ($this->translation as $string_ident => $obj_arr)
			{
                // DEBUG
                if (DEBUG > 0)
                	$string_value = $string_ident;

				foreach ($obj_arr as $obj)
				{
					$this->node = $obj->node;
                    // add translation to appropriate xml type
					if ($obj->type == XML_VALUE)
						@$this->node->appendChild($this->doc->createTextNode($string_value));
					elseif ($obj->type == XML_ATTRIBUTE)
						@$this->set_attribute($obj->name, $string_value);
					elseif ($obj->type == XML_TEXT)
						@$this->node->nodeValue = $string_value;
				}
			}			
		}

		// reset translation array and restore current node
		$this->translation = array();
		$this->node = $node;
	} // end ub_xml->translate()
	
    /**
    * return the DOMDocument object
    * 
    * @return       current xml object (DOMDocument object)
    */
    public function get_object()
    {
		$this->translate();
        return $this->doc;
    } // end ub_xml->get_object()

    /**
    * return the xml tree as a string
    * 
    * @return       xml tree (string)
    */
    public function get_tree()
    {
        $this->translate();
        return $this->doc->saveXML();
    } // end ub_xml->get_tree()

    /**
    * return tagname of the current node
    *
    * @return       tagname (string)
    */
    public function get_tagname()
    {
        return $this->node->tagName;
    } // end ub_xml->get_tagname()

    /**
    * return named attribute of the current node
    * 
    * @param        $name           attribute name (string)
    * @return       attribute value (string)
    */
    public function get_attribute($name, $parent_level = 1)
    {
        if ($parent_level > 1)
        {
            $node = $this->node;
            for ($i = 1; $i < $parent_level; $i++)
                $node = $node->parentNode;
            return $node->getAttribute($name);
        }
        return $this->node->getAttribute($name);
    } // end ub_xml->get_attribute()

    /**
    * get child node list of current node
    * 
    * @return       child list (DOMNodeList object)
    */
	public function get_child_nodes()
	{
		return $this->node->childNodes;
	}

    public function subnode_of($name)
    {
        $node = $this->node;

        while (($node = $node->parentNode) !== null)
            if (!($node instanceof DOMDocument) && $node->tagName == $name && !($node->parentNode instanceof DOMDocument) && $node->parentNode->tagName == 'root')
                return true;
        return false;
    }

    /**
    * return named attribute of the current node
    * 
    * @param        $node           node content (DOMNode object)
    */
	public function set_current_node($node)
	{
		$this->node = $node;
	}

    /**
    * removes the first node found with the given name
    * 
    * @param        $node           node content (DOMNode object)
    */
	public function remove($node = '')
	{
        if (trim($node) != '')
            if ($node = $this->node->getElementsByTagName($node)->item(0))
            {
                $this->node->removeChild($node);
                return true;
            }
            else
                return false;
        else
        {
    		$parent_node = $this->node->parentNode;
    		$parent_node->removeChild($this->node);
    		$this->node = $parent_node->lastChild;
    		$this->set_marker('last_child');
    		$this->set_marker('last_value');
    		$this->node = $parent_node;
        }
	}

    /**
    * save xml tree to file
    * 
    * @param        $file           filename (string)
    * @return       success (bool)
    */
    public function save($file)
    {
        if ((trim($file) != '' && file_exists($file) && is_writable($file)) || (trim($file) != '' && !file_exists($file) && touch($file)))
        {
            if (file_put_contents($file, $this->doc->saveXML()))
            {
                return TRUE;
            }
        }
        return FALSE;
    } // end ub_xml->save()

    /**
    * clear translations array
    * 
    */
    public function remove_translations()
    {
        $this->translation = array();
    }

} // end class ub_xml

?>