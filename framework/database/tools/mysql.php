<?php

/**
*
* uniBox 2.0 (winterspring)\n
* (c) Media Soma GbR
*
* 1.0   15.11.2006  jn      tools seperated, marked as final
*
*/

class ub_db_tools_mysql
{
    /**
    * class version
    * 
    */
    const version = '0.1.0';

	public function __construct()
	{
		$this->db = ub_db::get_instance();
	}

    public function check_integrity()
    {
        $foreign_keys = $data = $data_failed = array();
        
        // list tables
        if (($result = $this->db->query($this->get_special_query('tables'), 'failed to get tables')) && $result->num_rows() > 0)
            while (list($table) = $result->fetch_row())
            {
                if ($table_definition = $this->show_create_table($table))
                {
                    // get all foreign keys
                    $matches = array();
                    if (preg_match_all('/FOREIGN KEY \(`(.+)`\) REFERENCES `(.+)` \(`(.+)`\)/i', $table_definition, $matches, PREG_SET_ORDER))
                        foreach ($matches as $match)
                        {
                            if (stristr($match[1], '`, `'))
                            {
                                $match[1] = explode('`, `', $match[1]);
                                $match[3] = explode('`, `', $match[3]);
                                foreach ($match[1] as $key => $value)
                                    $foreign_keys[$table][$match[1][$key]] = array($match[2], $match[3][$key]);
                            }
                            else
                                $foreign_keys[$table][$match[1]] = array($match[2], $match[3]);
                        }
                }
            }

        // select foreign key values
        if (!empty($foreign_keys))
        {
            foreach ($foreign_keys as $source_table => $array)
            {
                $sql_string  = 'SELECT
                                  '.implode(', ', array_keys($array)).'
                                FROM
                                  '.$source_table;
                if (($result = $this->db->query($sql_string, 'failed to get data from: '.$source_table)) && $result->num_rows() > 0)
                {
                    while ($row = $result->fetch_row(FETCHMODE_ASSOC))
                        foreach ($row as $key => $value)
                            if ($value != null)
                                $data[$array[$key][0]][$array[$key][1]][] = $value;
                }
            }
        }
        
        array_walk_recursive($data = ub_functions::array_unique_recursive($data), array(&$this->db, 'cleanup'));
        foreach ($data as $table => $array)
            foreach ($array as $field => $data)
            {
                $sql_string  = 'SELECT
                                  COUNT('.$field.') AS count
                                FROM
                                  '.$table.'
                                WHERE
                                  '.$field.' IN (\''.implode('\', \'', $data).'\')';
                if (($result = $this->db->query($sql_string, 'failed to get data from: '.$source_table)) && $result->num_rows() == 1)
                {
                    list($count) = $result->fetch_row();
                    if ($count < count($data))
                    {
                        // get inconsistency details
                        $new_array = array();
                        $sql_string  = 'SELECT
                                          '.$field.'
                                        FROM
                                          '.$table.'
                                        WHERE
                                          '.$field.' IN (\''.implode('\', \'', $data).'\')';
                        $result = $this->db->query($sql_string, 'failed to get data from: '.$source_table);
                        while (list($value) = $result->fetch_row())
                            $new_array[] = $value;
                        $data_failed[$table] = array_diff($array[$field], $new_array);
                    }
                }
            }
        if (!empty($data_failed))
            return $data_failed;
        else
            return true;
    }

	public function create_constraint_from_structure($xml)
	{
		$attributes = $xml->attributes();
		$table_name = $attributes['name'];
		$sql_string = 'ALTER TABLE `'.$table_name.'`'."\n";

		// loop through fields
		$count_total = count($xml->constraint);
		$count = 0;
		foreach ($xml->constraint as $constraint)
		{
			$count++;

			// convert own fields to array			
			$own_fields = array();
			foreach ($constraint->field as $field)
				$own_fields[] = $field;

			// convert foreign fields to array
			$foreign_fields = array();
			foreach ($constraint->references->field as $field)
				$foreign_fields[] = $field;

			// get foreign data attributes
			$attributes = $constraint->references->attributes();
			
			// build sql string
			$sql_string .= '  ADD FOREIGN KEY (`'.implode('`, `', $own_fields).'`) REFERENCES '.(string)$attributes['table'].' (`'.implode('`, `', $foreign_fields).'`)';
			if (isset($constraint->on_delete))
				$sql_string .= ' ON DELETE '.(string)$constraint->on_delete;
			if (isset($constraint->on_update))	
				$sql_string .= ' ON UPDATE '.(string)$constraint->on_update;

			// break line
			if ($count != $count_total)
				$sql_string .= ",\n";
		}
		$this->db->query($sql_string, 'failed to create table \''.$table_name.'\'');
	}

	public function create_table_from_structure($xml)
	{
		$attributes = $xml->attributes();
		$table_name = $attributes['name'];
		$sql_string = 'CREATE TABLE `'.$table_name.'`'."\n".'('."\n";

		// loop through fields
		$count_total = count($xml->field);
		$count = 0;
		foreach ($xml->field as $field)
		{
			$count++;
			// set field name
			$attributes = $field->attributes();
			$sql_string .= '`'.$attributes['name'].'`';

			// set data type
			$attributes = $field->{'type-definition'}->attributes();
			switch ((string)$attributes['type'])
			{
				case 'integer':
					// set type
					switch ((int)$field->{'type-definition'}->size)
					{
						case 1:
							$sql_string .= ' tinyint';
							break;
						case 2:
							$sql_string .= ' smallint';
							break;
						case 3:
							$sql_string .= ' mediumint';
							break;
						case 4:
							$sql_string .= ' int';
							break;
						case 8:
							$sql_string .= ' bigint';
							break;
					}
					if (isset($field->{'type-definition'}->length))
						$sql_string .= '('.$field->{'type-definition'}->length.')';
					break;
				case 'float':
					switch ((int)$field->{'type-definition'}->size)
					{
						case 4:
							$sql_string .= ' float';
							break;
						case 8:
							$sql_string .= ' double';
							break;
					}
					break;
				case 'text':
					switch ((string)$field->{'type-definition'}->size)
					{
						case '255':
							$sql_string .= ' tinytext';
							break;
						case '65535':
							$sql_string .= ' text';
							break;
						case '16777215':
							$sql_string .= ' mediumtext';
							break;
						case '4294967295':
							$sql_string .= ' longtext';
							break;
					}
					break;
				case 'char':
					$sql_string .= ' char('.(int)$field->{'type-definition'}->length.')';
					break;
				case 'varchar':
					$sql_string .= ' varchar('.(int)$field->{'type-definition'}->length.')';
					break;
				case 'blob':
					switch ((string)$field->{'type-definition'}->size)
					{
						case '255':
							$sql_string .= ' tinyblob';
							break;
						case '65535':
							$sql_string .= ' blob';
							break;
						case '16777215':
							$sql_string .= ' mediumblob';
							break;
						case '4294967295':
							$sql_string .= ' longblob';
							break;
					}
					break;
				case 'date':
					$sql_string .= ' date';
					break;
				case 'datetime':
					$sql_string .= ' datetime';
					break;
				case 'time':
					$sql_string .= ' time';
					break;
				case 'enum':
					$values = array();
					foreach ($field->{'type-definition'}->value as $value)
						$values[] = (string)$value;
					$sql_string .= ' enum(\''.implode('\', \'', $values).'\')';
					break;
			}

			// set nullable
			if ($field->{'type-definition'}->unsigned == 1)
				$sql_string .= ' unsigned';

			// set nullable
			if ($field->nullable == 0)
				$sql_string .= ' NOT NULL';

			// set default value
			if (isset($field->{'default-value'}))
			{
				$attributes = $field->{'default-value'}->attributes();
				if (isset($attributes['null']) && (string)$attributes['null'] == 'true' && isset($field->nullable) && $field->nullable == 1)
					$sql_string .= ' default NULL';
				else
					$sql_string .= ' default \''.$field->{'default-value'}.'\'';
			}

			// set auto increment
			if ($field->{'auto-increment'} == 1)
				$sql_string .= ' auto_increment';

			// break line
			if ($count != $count_total || count($xml->key) > 0)
				$sql_string .= ",\n";
		}

		// loop through keys
		$count_total = count($xml->key);
		$count = 0;
		foreach ($xml->key as $key)
		{
			$count++;
			
			// convert fields to array
			$fields = array();
			foreach ($key->field as $field)
				$fields[] = $field;

			// set key type
			$attributes = $key->attributes();
			switch ((string)$attributes['type'])
			{
				case 'primary':
					$sql_string .= 'PRIMARY KEY';
					break;
				case 'unique':
					$sql_string .= 'UNIQUE KEY `'.$attributes['name'].'`';
					break;
				case 'index':
					$sql_string .= 'KEY `'.$attributes['name'].'`';
					break;
			}
			$sql_string .= ' (`'.implode('`, `', $fields).'`)';

			// break line
			if ($count != $count_total)
				$sql_string .= ",\n";
		}
		$sql_string .= "\n".') ENGINE=InnoDB DEFAULT CHARSET=utf8';
		$this->db->query($sql_string, 'failed to create table \''.$table_name.'\'');
	}

    /**
    * return dbms specific versions of general queries
    * 
    * @param        $table        	table to query structure from
    * @return       node (DOMNode)	DOMNode containing structure description
    */
    public function get_table_structure($table)
    {
    	// type map
    	$types = array( // numeric types
    					'tinyint'		=>	array('integer', 1),
    					'smallint'		=>	array('integer', 2),
    					'mediumint' 	=>	array('integer', 3),
    					'int'			=>	array('integer', 4),
    					'bigint'		=>	array('integer', 8),
    					'float'			=> 	array('float', 4),
    					'double'		=>	array('float', 8),
    					'real'			=>	array('float', 8),
    					
    					// string types
    					'tinytext'		=>	array('text', 255),
    					'text'			=>	array('text', 65535),
    					'mediumtext'	=>	array('text', 16777215),
    					'longtext'		=>	array('text', 4294967295),
    					'char'			=>	array('char', null),
    					'varchar'		=>	array('varchar', null),
    					
    					// binary types
    					'tinyblob'		=>	array('blob', 255),
    					'blob'			=>	array('blob', 65535),
    					'mediumblob'	=>	array('blob', 16777215),
    					'longblob'		=>	array('blob', 4294967295),
    					
    					// date/time types
    					'date'			=>	array('date', null),
    					'datetime'		=>	array('datetime', null),
    					'time'			=>	array('time', null),
    					
    					// misc types
    					'enum'			=>	array('enum', null)
    				);
    					
		$xml = new ub_xml();
		
		// add table node
		$xml->add_node('table');
		$xml->set_attribute('name', $table);

		// get fields
		$result = $this->db->query('DESCRIBE '.$table);
		while (list($field, $type, $null, $key, $default, $extra) = $result->fetch_row())
		{
			// add field node
			$xml->add_node('field');
			$xml->set_attribute('name', $field);
			
			// identify type
			$matches = array();
			preg_match('/([a-z]+)(\((.+)\))?( ([a-z]+))*/i', $type, $matches);
			
			$type = $matches[1];
			$length = isset($matches[3]) ? $matches[3] : null;
			$unsigned = isset($matches[5]);
			
			// add type-definition node
			$xml->add_node('type-definition');
			
			// get type mapping
			list($c_type, $c_size) = $types[$type];

			// check if type is enum
			if ($c_type == 'enum')
			{
				$values = explode(',', $length);
				foreach ($values as $value)
					$xml->add_value('value', str_replace('\'', '', $value));
			}
			
			// add type info
			$xml->set_attribute('type', $c_type);
			if ($c_size !== null)
				$xml->add_value('size', $c_size);

			// add length
			if ($length && $type != 'enum')
				$xml->add_value('length', $length);

			// set sign mode
			$xml->add_value('unsigned', $unsigned ? 1 : 0);
			
			// close type-definition node
			$xml->parse_node();

			// add general attributes
			$xml->add_value('nullable', $null == 'YES' ? 1 : 0);
			if ($c_type != 'blob' && $c_type != 'text' && (($c_type == 'char' || $c_type == 'varchar') || trim($default) != ''))
				$xml->add_value('default-value', $default);
			$xml->add_value('auto-increment', $extra == 'auto_increment' ? 1 : 0);

			// close field node
			$xml->parse_node();
		}
		
		// get keys
		$current_key = null;
		$result = $this->db->query('SHOW INDEX FROM '.$table);
		while ($key = $result->fetch_row(FETCHMODE_ASSOC))
		{
			if ($current_key != $key['Key_name'])
			{
				if ($current_key !== null)
					$xml->parse_node();

				// determine key type
				if ($key['Key_name'] == 'PRIMARY')
					$type = 'primary';
				elseif ($key['Non_unique'] == 0)
					$type = 'unique';
				else
					$type = 'index';
					
				$xml->add_node('key');
				$xml->set_attribute('name', $key['Key_name']);
				$xml->set_attribute('type', $type);

				$current_key = $key['Key_name'];
			}
			$xml->add_value('field', $key['Column_name']);
		}
		$xml->parse_node();

		// get constraints
		$result = $this->db->query('SHOW CREATE TABLE '.$table);
		list ($foo, $result) = $result->fetch_row();
		
		// match constraints
		$matches = array();
		preg_match_all('/CONSTRAINT `.+` FOREIGN KEY \(`(.+)`\) REFERENCES `(.+)` \(`(.+)`\)( ON DELETE ([a-z]+))?( ON UPDATE ([a-z]+))? ?/im', $result, $matches, PREG_SET_ORDER);
		foreach ($matches as $match)
		{
			$xml->add_node('constraint');
			if (stristr($match[1], '`, `'))
			{
				$match[1] = explode('`, `', $match[1]);
				foreach ($match[1] as $field)
					$xml->add_value('field', $field);
			}
			else
				$xml->add_value('field', $match[1]);
			$xml->add_node('references');
			$xml->set_attribute('table', $match[2]);
			if (stristr($match[3], '`, `'))
			{
				$match[3] = explode('`, `', $match[3]);
				foreach ($match[3] as $field)
					$xml->add_value('field', $field);
			}
			else
				$xml->add_value('field', $match[3]);
			$xml->parse_node();
			if (!empty($match[5]))
				$xml->add_value('on_delete', $match[5] == 'SET' ? 'SET NULL' : $match[5]);
			if (!empty($match[7]))
				$xml->add_value('on_update', $match[7] == 'SET' ? 'SET NULL' : $match[7]);
			$xml->parse_node();
		}

		// close table node
		$xml->parse_node();

		$xml = $xml->get_object();
		
		// get table structure node
		$nodelist = $xml->documentElement->getElementsByTagName('table');
		$node = $nodelist->item(0);

		return $node;
    }

	public function show_create_table($table)
	{
		$result = $this->db->query('SHOW CREATE TABLE '.$table, 'failed to get table structure');
		list ($foo, $result) = $result->fetch_row();
		return $result;
	}

	public function get_primary_key($table)
	{
		$primary_key = false;
		if (($result = $this->db->query('SHOW INDEX FROM '.$table)) && $result->num_rows() > 0)
			while ($row = $result->fetch_row(MYSQL_ASSOC))
				if ($row['Key_name'] == 'PRIMARY')
					$primary_key[] = $row['Column_name'];
		return $primary_key;
	}

    /**
    * return dbms specific versions of general queries
    * 
    * @param        $type           type of query to return
    * @return       sql query (string)
    */
    public function get_special_query($type)
    {
        switch ($type) 
        {
            case 'tables':
                return 'SHOW TABLES';
            case 'databases':
                return 'SHOW DATABASES';
            default:
                return null;
        }
    }

	public function drop_foreign_keys($table)
	{
		// get constraints
		$result = $this->db->query('SHOW CREATE TABLE '.$table);
		list ($foo, $result) = $result->fetch_row();
		
		// match constraints
		$matches = array();
		preg_match_all('/CONSTRAINT `(.+)` FOREIGN KEY/im', $result, $matches, PREG_SET_ORDER);
		foreach ($matches as $match)
			$this->db->query('DROP FOREIGN KEY '.$match[1], 'failed to drop foreign key \''.$match[1].'\' on table \''.$table.'\'');
	}

	public function drop_table($table)
	{
		$this->drop_foreign_keys($table);
		$this->unibox->db->query('DROP TABLE '.$table, 'failed to drop table \''.$table.'\'');
	}

}