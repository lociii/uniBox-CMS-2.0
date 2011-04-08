<?php

class ub_update
{
	const version = '0.1.0';
	
	protected $unibox = null;
	protected $module_ident = null;
	
	public function __construct($module_ident)
	{
		$this->unibox = ub_unibox::get_instance();
		$this->module_ident = $module_ident;
	}

	public function update()
	{
		// get version of installed module
		$sql_string =  'SELECT
						  module_version
						FROM
						  sys_modules
						WHERE
						  module_ident = \''.$this->unibox->db->cleanup($this->module_ident).'\'';
		$result = $this->unibox->db->query($sql_string, 'failed to retrieve module version');
		if ($result->num_rows() != 1)
		{
			$msg = new ub_message(MSG_ERROR);
			$msg->add_text('TRL_FAILED_TO_DETERMINE_MODULE_VERSION');
			$msg->display();
			return false;
		}
		list($module_version) = $result->fetch_row();
		$result->free();

		// load update script
		if (!is_readable(DIR_MODULES.$this->module_ident.'/update.php'))
		{
			$msg = new ub_message(MSG_ERROR);
			$msg->add_text('TRL_FAILED_TO_LOAD_UPDATE_SCRIPT');
			$msg->display();
			return false;
		}
		include(DIR_MODULES.$this->module_ident.'/update.php');

		// check if the update class was loaded
		$classname = 'ub_'.$this->module_ident.'_update';
		if (!in_array($classname, get_declared_classes()))
		{
			$msg = new ub_message(MSG_ERROR);
			$msg->add_text('TRL_INVALID_UPDATE_SCRIPT');
			$msg->add_newline();
			$msg->add_text('TRL_UPDATE_CLASS_NOT_FOUND');
			$msg->display();
			return false;
		}

		// try create update instance
		$update = new $classname();
		if (!$update || get_class($update) != $classname)
		{
			$msg = new ub_message(MSG_ERROR);
			$msg->add_text('TRL_INVALID_UPDATE_SCRIPT');
			$msg->add_newline();
			$msg->add_text('TRL_UPDATE_CLASS_INVALID');
			$msg->display();
			return false;
		}

		// check if the update class contains the required update method
		$method = 'update_'.str_replace('.', '_', $module_version);
		if (!method_exists($update, $method))
		{
			$msg = new ub_message(MSG_ERROR);
			$msg->add_text('TRL_INVALID_UPDATE_SCRIPT');
			$msg->add_newline();
			$msg->add_text('TRL_INCOMPATIBLE_UPDATE_CLASS');
			$msg->display();
			return false;
		}

		// finally, call the update method
		$update->$method();
		if (!$update->get_result())
		{
			$msg = new ub_message(MSG_ERROR);
			$msg->add_text('TRL_UPDATE_FAILED');
			$msg->display();
			return false;
		}

		return true;
	}
}

class ub_update_tools
{
	const version = 0.1;
	public $unibox;

	public function __construct()
	{
		$this->unibox = ub_unibox::get_instance();
	}

    public function rename_action($old, $new)
    {
        $sql_string  = 'UPDATE
						  sys_actions
						SET
						  action_ident = \''.$new.'\'
						WHERE
						  action_ident = \''.$old.'\'';
		$this->unibox->db->query($sql_string, 'failed to rename action \''.$old.'\' to \''.$new.'\'');
    }

    public function rename_alias($old, $new)
    {
        $sql_string  = 'UPDATE
						  sys_alias
						SET
						  alias = \''.$new.'\'
						WHERE
						  alias = \''.$old.'\'';
		$this->unibox->db->query($sql_string, 'failed to rename alias \''.$old.'\' to \''.$new.'\'');
    }

    public function rename_alias_group($old, $new)
    {
        $sql_string  = 'UPDATE
						  sys_alias_groups
						SET
						  alias_group_ident = \''.$new.'\'
						WHERE
						  alias_group_ident = \''.$old.'\'';
		$this->unibox->db->query($sql_string, 'failed to rename alias group \''.$old.'\' to \''.$new.'\'');
    }

}

?>