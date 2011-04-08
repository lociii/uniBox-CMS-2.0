<?php

/**
*
* uniBox 2.0 (winterspring)\n
* (c) Media Soma GbR
*
* 0.1       31.01.2006	pr      1st release
*
*/

class ub_ucm
{
    /**
    * class version
    */
    const version = '0.1.0';

    /**
    * unibox framework instance
    */
    protected $unibox;

	/**
	 * module ident
	 */
	protected $module_ident = null;
	
	/**
	 * type of content this object is holding
	 */
	protected $content_type = null;

	/**
	 * all content types
	 */
	protected $content_types = array();

	/**
	 * primary constraints for current object
	 */
	protected $primary_constraints = array();
	
	/**
	 * contraints for lower content objects
	 */
	protected $constraints = array();

	/**
	 * limits for sql query
	 */
	protected $limit = null;
	
	/**
	 * sort order for collection
	 */
	protected $sort_order = array();
	
    /**
    * returns class version
    * 
    * @return       version-number (float)
    */
    public static function get_version()
    {
        return ub_ucm::version;
    } // end get_version()
    
    /**
    * class constructor
    */
    public function __construct($module_ident)
    {
        $this->unibox = ub_unibox::get_instance();
        $this->module_ident = $module_ident;
        $this->unibox->config->load_config($module_ident);
        
        // query all supported content-types for selected module
        $sql_string  = 'SELECT
						  content_type AS type,
						  parent_content_type AS parent,
						  parent_dependency AS dependency,
						  content_table AS `table`,
						  si_content_descr AS descr,
						  si_content_descr_count AS count_descr,
						  field_map AS `fields`
						FROM
                          sys_ucm
						WHERE
						  module_ident = \''.$module_ident.'\'';
		$result = $this->unibox->db->query($sql_string, 'failed to query supported content-types');
		if ($result->num_rows() < 1)
			return false;

		while ($row = $result->fetch_row(FETCHMODE_ASSOC))
		{
			if ($fields = unserialize($row['fields']))
				$row['fields'] = $fields;
			else
				$row['fields'] = array();

			$this->content_types[$row['type']] = new stdClass();
			foreach ($row as $key => $value)
				$this->content_types[$row['type']]->$key = $value;
		}
		$result->free();
    } // end __construct()

	protected function is_supported_query_type($type, $field = null)
	{
		$return = isset($this->content_types[$type]) && ($this->content_types[$type]->parent == $this->content_type);

		if ($field !== null)
			$return = $return && isset($this->content_types[$type]->fields[$field]);

		return $return;
	}
	
	protected function create_content_object($content_type, $data)
	{
		$content = new ub_ucm_content($content_type, $this->content_types, $data);
		foreach ($this->primary_constraints as $constraint)
			$content->set_primary_constraint('parent_'.$constraint->ident, $constraint->value, $constraint->operator, $constraint->glue, $constraint->quotes);

		if (isset($data['id']))
			$content->set_primary_constraint('id', $data['id']);

		return $content;
	}
	
	public function get_next_content_type()
	{
		$content_types = array_keys($this->list_content_types());
		foreach ($content_types as $type)
			if ($this->content_types[$type]->parent == $this->content_type)
				return $type;

		return reset($content_types);
	}
			
	public function list_content_types()
	{
		$return = array();
		foreach ($this->content_types as $type => $obj)
			if ($this->is_supported_query_type($type))
				$return[$type] = $obj->descr;
		
		return $return;
	}
	
	public function set_constraint($ident, $value, $operator = '=', $glue = 'AND', $quotes = true)
	{
		$this->constraints[] = new StdClass();
		end($this->constraints);
		$this->constraints[key($this->constraints)]->ident = $ident;
		$this->constraints[key($this->constraints)]->value = $value;
		$this->constraints[key($this->constraints)]->operator = $operator;
		$this->constraints[key($this->constraints)]->glue = $glue;
		$this->constraints[key($this->constraints)]->quotes = $quotes;
	}
	
	public function set_primary_constraint($ident, $value, $operator = '=', $glue = 'AND', $quotes = true)
	{
		$this->primary_constraints[] = new StdClass();
		end($this->primary_constraints);
		$this->primary_constraints[key($this->primary_constraints)]->ident = $ident;
		$this->primary_constraints[key($this->primary_constraints)]->value = $value;
		$this->primary_constraints[key($this->primary_constraints)]->operator = $operator;
		$this->primary_constraints[key($this->primary_constraints)]->glue = $glue;
		$this->primary_constraints[key($this->primary_constraints)]->quotes = $quotes;
	}
	
	public function set_limit($offset, $length)
	{
		$this->limit = new StdClass();
		$this->limit->offset = $offset;
		$this->limit->length = $length;
	}

	public function set_order($ident, $order = 'ASC')
	{
		$this->sort_order[$ident] = $order;
	}
	
	public function reset()
	{
		$this->constraints = array();
		$this->primary_constraints = array();
		$this->limit = null;
		$this->sort_order = array();
	}
	
	public function get_content_by_id($id, $type = null)
	{
		if ($type == null)
			$type = $this->get_next_content_type();
		
		if (!$this->is_supported_query_type($type, 'id'))
			return false;

		$this->set_constraint('id', $id);
		return $this->get_content($type)->get_content_by_id($id);
	}
	
	public function get_content_by_category($id, $type = null)
	{
		if ($type == null)
			$type = $this->get_next_content_type();
		
		if (!$this->is_supported_query_type($type, 'category'))
            throw new ub_exception_runtime('invalid ucm request');
			
		$this->set_constraint('category', $id);
		return $this->get_content($type);
	}
	
	public function get_content($type = null, $functions = array(), $function_fields_only = false)
	{
		if ($type === null)
			$type = $this->get_next_content_type();
					
		$content = $this->content_types[$type];

		$sql = new ub_sql_builder();
		if ($function_fields_only && !empty($functions))
		{
			foreach ($functions as $ident => $function)
				$sql->add_field($content->fields[$ident], null, $ident, null, $function);
		}
		else
		{
			foreach ($content->fields as $ident => $field)
			{
				if (isset($functions[$ident]))
					$sql->add_field($field, null, $ident, null, $functions[$ident]);
				else
					$sql->add_field($field, null, $ident);
			}
		}
		
		$sql->add_table($content->table);
		
		// process constraints
		foreach ($this->constraints as $constraint)
			$sql->add_condition($content->fields[$constraint->ident], $constraint->value, $constraint->operator, $constraint->glue, $constraint->quotes);

		// process primary constraints
		foreach ($this->primary_constraints as $constraint)
			$sql->add_condition($content->fields['parent_'.$constraint->ident], $constraint->value, $constraint->operator, $constraint->glue, $constraint->quotes);
		
		// process sort order
		foreach ($this->sort_order as $ident => $order)
			$sql->add_sort($content->fields[$ident], $order);

		// process limit if set
		if ($this->limit != null)
			$this->unibox->db->limit_query($this->limit->offset, $this->limit->length);

		// query database for requested content
		$result = $this->unibox->db->query($sql->get_string(), 'ucm: failed to retrieve content');
		$collection = new ub_ucm_collection($content);
		if ($result->num_rows() > 0)
		{
			while ($row = $result->fetch_row(FETCHMODE_ASSOC))
				$collection->append((!$function_fields_only) ? $row['id'] : null, $this->create_content_object($type, $row));
			$result->free();
		}

		return $collection;
	}
	
}

class ub_ucm_content extends ub_ucm
{
	/**
	 * new values for fields used for update
	 */
	protected $updates = array();
	
    /**
    * class constructor
    */
    public function __construct($content_type, $content_types, $data)
    {
        $this->unibox = ub_unibox::get_instance();
        $this->content_type = $content_type;
        $this->content_types = $content_types;
        $this->data = $data;
    } // end __construct()
    
    public function get_content_descr()
    {
    	return $this->content_types[$this->content_type]->descr;
    }
    
    public function list_data()
    {
    	return array_keys($this->data);
    }
    
    public function get_data()
    {
    	return $this->data;
    }
    
    public function get_data_by_id($id)
    {
    	if (isset($this->data[$id]))
    		return $this->data[$id];
    	else
    		return false;
    }
    
	public function has_subcontent()
	{
		$content_types = array_keys($this->list_content_types());
		foreach ($content_types as $type)
			if ($this->content_types[$type]->parent == $this->content_type)
				return true;

		return false;
	}
	
	public function set($id, $value)
	{
		$this->updates[$id] = $value;
	}
	
	public function update()
	{
		if (count($this->updates) == 0)
			return 0;

		$content = $this->content_types[$this->content_type];
		
		$sql = new ub_sql_builder(SQL_QUERY_UPDATE);
		$sql->add_table($content->table);
		foreach ($this->updates as $id => $value)
			$sql->add_field($content->fields[$id], null, null, $value);
			
		foreach ($this->primary_constraints as $constraint)
			$sql->add_condition($content->fields[$constraint->ident], $constraint->value, $constraint->operator, $constraint->glue);

		$result = $this->unibox->db->query($sql->get_string(), 'ucm: failed to update content');
		$affected_rows = $this->unibox->db->affected_rows();
		$result->free();
		return $affected_rows;
	}
	
	public function delete($delete_subcontent = true)
	{
		$content = $this->content_types[$this->content_type];

		$sql = new ub_sql_builder(SQL_QUERY_DELETE);
		$sql->add_table($content->table);
		
		foreach ($this->primary_constraints as $constraint)
			$sql->add_condition($content->fields[$constraint->ident], $constraint->value, $constraint->operator, $constraint->glue);

		if ($delete_subcontent && $this->has_subcontent())
			if ($subcontent = $this->get_content())
				$subcontent->delete($delete_subcontent);
		
		$result = $this->unibox->db->query($sql->get_string(), 'ucm: failed to delete content');
		$affected_rows = $this->unibox->db->affected_rows();
		return $affected_rows;
	}
}

class ub_ucm_collection
{
	/**
	 * content type definition of collections content
	 */
	protected $content_type = null;
	
	/**
	 * all content items in this collection
	 */
	protected $data = array();

	/**
	 * new values for fields used for update
	 */
	protected $updates = array();

	public function __construct($content_type_definition)
	{
		$this->content_type = $content_type_definition;
	}
		
	public function append($id, $value)
	{
		if ($id === null)
			$this->data[] = $value;
		else
			$this->data[$id] = $value;
	}
	
	public function count()
	{
		return count($this->data);
	}
	
	public function get_content_by_id($id)
	{
		if (isset($this->data[$id]))
			return $this->data[$id];
		else
			return false;
	}
	
	public function get_content()
	{
		return $this->data;
	}
	
	public function set($id, $value)
	{
		$this->updates[$id] = $value;
	}
	
	public function update()
	{
		if (count($this->updates) == 0)
			return 0;

		$return = 0;
		foreach ($this->data as $content)
		{
			foreach ($this->updates as $id => $value)
				$content->set($id, $value);
			
			$return += $content->update();
		}
		
		return $return;
	}

	public function delete($delete_subcontent = true)
	{
		$return = 0;
		foreach ($this->data as $content)
			$return += $content->delete($delete_subcontent);
		
		return $return;
	}
	
	public function get_content_descr()
	{
		return $this->content_type->descr;
	}
	
	public function get_count_string()
	{
		$unibox = ub_unibox::get_instance();
		return $unibox->translate($this->content_type->count_descr, array($this->count()));
	}
}

?>