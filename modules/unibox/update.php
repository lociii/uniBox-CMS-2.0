<?php

class ub_unibox_update
{
    protected $updater = null;
    protected $result = false;

    public function get_result()
    {
        return $this->result;
    }

    public function __construct()
    {
        $this->updater = new ub_update_tools();
    }

	// update from version 0.1.0 to 0.1.1
	public function update_0_1_0()
	{
		$this->result = true;
	}
}

?>