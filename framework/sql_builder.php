<?php

/**
*
* uniBox 2.0 (winterspring)\n
* (c) Media Soma GbR
*
* 0.1       15.03.2006  pr      1st release\n
* 0.2       05.05.2006  jn      added reset function
*
*/

class ub_sql_builder
{
	protected $result_type = SQL_QUERY_SELECT;
	
    // array of strings -> fields to be selected
    protected $fields = array();
    
    // array of StdClass -> tables to select from
    protected $tables = array();
    
    // array of StdClass -> join conditions
    protected $join_conditions = array();
    
    // array of array/StdClass -> conditions
    protected $condition_groups = array();
    
    // max yet used condition group id
    protected $condition_groups_max_id = 0;
    
    // current group to insert condition
    protected $condition_groups_current_id = 0;
    
    // array of integer -> parent id of each condition group
    protected $condition_groups_parent_ids = array();
    
    // sort fields
    protected $sort_order = array();
    
    // glue it or not
    protected $glue = false;
    
    // constructor -> initalize standard condition group
    public function __construct($result_type = SQL_QUERY_SELECT)
    {
    	$this->query_type = $result_type;
        $this->condition_groups[0] = array();
    }
    
    public function reset($result_type = SQL_QUERY_SELECT)
    {
        $this->query_type = $result_type;
        $this->fields = array();
        $this->tables = array();
        $this->join_conditions = array();
        $this->condition_groups = array();
        $this->condition_groups_max_id = 0;
        $this->condition_groups_current_id = 0;
        $this->condition_groups_parent_ids = array();
        $this->sort_order = array();
        $this->glue = false;
    }
    
    // add a field to select
    public function add_field($field, $table = null, $alias = null, $value = null, $function = null, $value_null = false)
    {
        if ($table !== null)
            $table .= '.';
        if ($value === null && !$value_null)
        {
            if ($function !== null)
                $field = $function.'('.$table.$field.')';
            else
                $field = $table.$field;
            if ($alias !== null && $value === null)
            $field .= ' AS '.$alias;
        }
        elseif ($value_null)
        	$field = $field.' = NULL';
        else
            $field = $field.' = \''.$value.'\'';

        $this->fields[] = $field;
    }
    
    // add a table to select from
    public function add_table($table, $alias = null, $join = null)
    {
        $data = new StdClass;
        $data->table = $table;
        $data->alias = $alias;
       	$data->join = $join;
        $this->tables[] = $data;
    }

    // add a condition
    public function add_condition($field, $value, $operator = '=', $glue = 'AND', $quotes = true)
    {
        $data = new StdClass;
        $data->field = $field;
        $data->value = $value;
        $data->operator = $operator;
        $data->glue = $glue;
        $data->quotes = $quotes;
        $this->condition_groups[$this->condition_groups_current_id][] = $data;
    }

    // begin new condition group (bracket)
    public function begin_condition_group()
    {
        // increase id
        $this->condition_groups_max_id++;
        // save parent of new group
        $this->condition_groups_parent_ids[$this->condition_groups_max_id] = $this->condition_groups_current_id;
        // initialize the new group
        $this->condition_groups[$this->condition_groups_max_id] = array();
        // add reference of new group as child of parent one
        $this->condition_groups[$this->condition_groups_current_id][] = &$this->condition_groups[$this->condition_groups_max_id];
        // switch current group
        $this->condition_groups_current_id = $this->condition_groups_max_id;
    }
    
    // close condition group (bracket)
    public function end_condition_group()
    {
        $this->condition_groups_current_id = $this->condition_groups_parent_ids[$this->condition_groups_current_id];
    }

    // add a condition to join tables
    public function add_join_condition($table_a, $table_b, $field_a, $field_b)
    {
        if (!isset($this->join_conditions[$table_a]))
            $this->join_conditions[$table_a] = array();
        $this->join_conditions[$table_a][] = array($table_b, $field_a, $field_b);
    }

	// add a sort order
	public function add_sort($field, $order = 'ASC')
	{
		$this->sort_order[] = $field.' '.$order;
	}
	
    // build where statement
    public function build_where($array)
    {
        // loop through given array of items
        foreach ($array as $key => $item)
        {
            // add item
            if ($item instanceof StdClass)
            {
                $this->string .= (($this->glue) ? ' '.$item->glue : '').' '.$item->field.' '.$item->operator.' '.(($item->quotes) ? '\'' : '').$item->value.(($item->quotes) ? '\'' : '');
                $this->glue = true;
            }
            // process new bracket
            elseif (is_array($item))
            {
                if ($key != 0)
                    $this->string .= ' '.$item[0]->glue;
                $this->string .= ' (';
                $this->glue = false;
                $this->build_where($item);
                $this->string .= ')';
            }
        }
    }

    // build sql string
    public function get_string()
    {
		switch ($this->query_type)
		{
            case SQL_QUERY_SELECT_DISTINCT:
			case SQL_QUERY_SELECT:
	            // add fields
	            $this->string = 'SELECT ';
                if ($this->query_type == SQL_QUERY_SELECT_DISTINCT)
                    $this->string .= 'DISTINCT ';
	            $this->string .= implode(', ', $this->fields);
	
	            // add tables and join conditions
	            $this->string .= ' FROM';
	            foreach ($this->tables AS $key => $table)
	            {
	                if ($key != 0)
	                {
		                if ($table->join !== null)
		                    $this->string .= ' '.$table->join.' JOIN';
		                else
		                	$this->string .= ',';
	                }
	                $this->string .= ' '.$table->table;
	
	                if ($table->alias !== null)
	                {
	                    $this->string .= ' AS '.$table->alias;
	                    if (isset($this->join_conditions[$table->alias]) && $table->join !== null)
	                        $conditions = $this->join_conditions[$table->alias];
	                }
	                elseif (isset($this->join_conditions[$table->table]) && $table->join !== null)
	                    $conditions = $this->join_conditions[$table->table];
	                
	                if (isset($conditions))
	                {
	                    $this->string .= ' ON (';
                        $last = end($conditions);
	                    foreach ($conditions AS $condition)
                        {
	                        $this->string .= ' '.(isset($table->alias) ? $table->alias : $table->table).'.'.$condition[1].' = '.$condition[0].'.'.$condition[2];
                            if ($condition != $last)
                                $this->string .= ' AND';
                        }
	                    $this->string .= ')';
	                }
	            }
	            break;
	            
			case SQL_QUERY_INSERT:
	            $this->string = 'INSERT INTO ';
	            
	            // FIX: support multiple tables
	            $table = $this->tables[0];
	            
	            $this->string .= $table->table;
	            if ($table->alias !== null)
	            	$this->string .= ' AS '.$table->alias;
	            
	            // add fields
	            $this->string .= ' SET ';
	            $this->string .= implode(', ', $this->fields);
	            
	            break;

			case SQL_QUERY_UPDATE:
	            $this->string = 'UPDATE ';
	            
	            // FIX: support multiple tables
	            $table = $this->tables[0];
	            
	            $this->string .= $table->table;
	            if ($table->alias !== null)
	            	$this->string .= ' AS '.$table->alias;
	            
	            // add fields
	            $this->string .= ' SET ';
	            $this->string .= implode(', ', $this->fields);
	            
	            break;
	            
			case SQL_QUERY_DELETE:
	            $this->string = 'DELETE FROM ';
	            
	            // FIX support multiple tables
	            $table = $this->tables[0];
	            
	            $this->string .= $table->table;
	            if ($table->alias !== null)
	            	$this->string .= ' AS '.$table->alias;

	            break;
		}					

        // add conditions
        if (isset($this->condition_groups[0]) && count($this->condition_groups[0]) > 0)
        {
        	$this->string .= ' WHERE';
            $this->build_where($this->condition_groups[0]);
        }

		// add sort order
		if (count($this->sort_order) > 0 && $this->query_type == SQL_QUERY_SELECT)
		{
			$this->string .= ' ORDER BY ';
			$this->string .= implode(', ', $this->sort_order);
		}
		
        // return sql string
        return $this->string;
    }
}

?>