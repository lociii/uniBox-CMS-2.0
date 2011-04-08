<?php

/**
*
* uniBox 2.0 (winterspring)\n
* (c) Media Soma GbR
*
* 0.1       18.03.2005  pr      1st release\n
* 0.2       04.04.2005	pr		added formatting functions\n
* 0.21      05.04.2005	pr		added comments\n
* 0.3       28.03.2006  jn,pr   changed to support multiple messages\n
*                               switched content to it's own message node
* 0.4       20.09.2006  jn      changed summary processing
*
*/

class ub_message
{
    /**
    * class version
    */
    const version = '0.1.0';

    /**
    * unibox framework object
    */
	protected $unibox;

    protected $no_restore = false;

	/**
	 * form object
	 */
	public $form = null;
    
    protected $summary = false;
	
    /**
    * returns class version
    * 
    * @return       version-number (float)
    */
    public static function get_version()
    {
        return ub_message::version;
    } // end get_version()
	
    /**
    * class constructor - loads framework configuration
    *
    * @param        $msg_type               message type constant (int)
    */
	public function __construct($msg_type, $delete_content = true)
	{
        /* require translations
         * TRL_MSG_TYPE_ERROR
         * TRL_MSG_TYPE_SUCCESS
         * TRL_MSG_TYPE_QUESTION
         * TRL_MSG_TYPE_WARNING
         * TRL_MSG_TYPE_NOTICE
         * TRL_MSG_TYPE_INFO
         */

		$this->unibox = ub_unibox::get_instance();
        $this->delete_content = $delete_content;

		// delete any existing content node
        if ($this->delete_content)
            $this->unibox->xml_reset(array('content'));

        // set content title
        $this->unibox->add_location('TRL_MSG_TYPE_'.$msg_type, null, true);

        $this->unibox->load_template('shared_message');
        $this->unibox->load_template('shared_ubc');

        $this->unibox->xml->goto('messages');
        $this->unibox->xml->add_node('message');
		$this->unibox->xml->add_value('type', $msg_type);

		$this->unibox->xml->add_value('title', 'TRL_MSG_TYPE_'.$msg_type, TRUE);
		$this->unibox->xml->add_node('ubc');
	} // end __construct()

    /**
    * adds a new text to the message
    * 
    * @param        $si_text                text (string)
    * @param        $trl_args               translation arguments (array)
    */
    public function add_text($si_text, $trl_args = array(), $translate = true)
    {
        $this->unibox->xml->add_text($si_text, $translate, $trl_args);
    } // end add_text()

	/**
	* adds a new line
	* 
    * @param        $count                  number of new lines to add (int)
	*/
	public function add_newline($count = 1)
	{
		for ($i = 0; $i < $count; $i++)
		{
            if ($this->summary)
            {
                $this->unibox->xml->add_node('table_row');
                $this->unibox->xml->add_node('table_cell');
                $this->unibox->xml->set_attribute('colspan', '2');
                $this->unibox->xml->add_value('br');
                $this->unibox->xml->parse_node();
                $this->unibox->xml->parse_node();
            }
            else
                $this->unibox->xml->add_value('br');
		}
	} // end add_newline()
	
	/**
	* adds a new separator
	* 
	*/
	public function add_separator()
	{
		$this->unibox->xml->add_value('separator');
	} // end add_separator()
	
	/**
    * begins a new list
    * 
    * @param        $listtype               list type (string)
    * @param        $vistype                vis type (string)
    */
	public function begin_list($listtype = 'ul', $vistype = '')
	{
		$this->unibox->xml->add_node('list');
		$this->unibox->xml->set_attribute('listtype', $listtype);
		$this->unibox->xml->set_attribute('vistype', $vistype);
	} // end begin_list()

	/**
    * adds a new listentry to the current list
    * 
    * @param        $si_text                text contained in the entry (string)
    * @param        $trl_args               translation arguments (array)
    */
	public function add_listentry($si_text, $translate = true, $trl_args = array())
	{
		$this->unibox->xml->add_value('listentry', $si_text, $trl_args, $translate);
	} // end add_listentry()
	
	/**
    * finishes current list
    * 
    */
	public function end_list()
	{
		$this->unibox->xml->parse_node();
	} // end end_list()

	/**
    * begins a new form, actually a buttonset
    * 
    * @param        $alias                  target alias (string)
    * @param        $name                   form name (string)
    */
	public function begin_form($name = 'message', $alias)
	{
        $this->unibox->load_template('shared_form');
		$this->unibox->xml->add_node('ubc_form');

		$this->form = ub_form_creator::get_instance();
		$this->form->begin_form($name, $alias);
	} // end begin_form()

	/**
    * finishes current form/buttonset
    */
	public function end_form()
	{
		$this->form->end_form();
		$this->unibox->xml->parse_node();
	} // end end_form()

    public function add_decision($submit_alias, $submit_trl, $cancel_alias, $cancel_trl, $form_name = 'message')
    {
        $this->begin_form($form_name, $submit_alias);
        $this->form->begin_buttonset(false);
        $this->form->submit($submit_trl);
        $this->form->cancel($cancel_trl, $cancel_alias);
        $this->form->end_buttonset();
        $this->end_form();
    }

    public function begin_summary()
    {
        $this->summary = true;
        $this->unibox->xml->add_node('table');
        $this->unibox->xml->set_attribute('width', '100%');
    }

    public function add_summary_content($description = '', $value = '', $translate_value = false, $trl_args_description = array(), $trl_args_value = array(), $state = null)
    {
        $this->unibox->xml->add_node('table_row');

        $this->unibox->xml->add_node('table_cell');
        $this->unibox->xml->set_attribute('width', '25%');
        $this->unibox->xml->set_attribute('vertical-align', 'top');
        $this->unibox->xml->add_text($description, true, $trl_args_description);
        $this->unibox->xml->parse_node();

        $this->unibox->xml->add_node('table_cell');
        if ($state !== null)
        {
            $this->unibox->xml->add_node('color');
            if ($state == true)
                $this->unibox->xml->set_attribute('value', '#00FF00');
            else
                $this->unibox->xml->set_attribute('value', '#FF0000');
        }
        if ($translate_value)
        	$this->unibox->xml->add_text($value, $translate_value, $trl_args_value);
        else
        {
	        $ubc = new ub_ubc();
	        $ubc->ubc2xml($value);
        }
        if ($state !== null)
            $this->unibox->xml->parse_node();
        $this->unibox->xml->parse_node();

        $this->unibox->xml->parse_node();
    }

    public function add_summary_headline($value, $translate_value = true, $trl_args_description = array())
    {
        $this->unibox->xml->add_node('table_row');

        $this->unibox->xml->add_node('table_cell');
        $this->unibox->xml->set_attribute('colspan', '2');
        $this->unibox->xml->add_value('strong', $value, $translate_value, $trl_args_description);
        $this->unibox->xml->parse_node();

        $this->unibox->xml->parse_node();
    }

    public function end_summary()
    {
        $this->unibox->xml->parse_node();
        $this->summary = false;
    }

	/**
    * adds a new link
    * 
    * @param        $url                    link url (string)
    * @param        $si_descr               link text (string)
    * @param        $trl_args               translation arguments (array)
    */
	public function add_link($url, $si_descr, $trl_args = array())
	{
		$this->unibox->xml->add_node('url');
		$this->unibox->xml->set_attribute('href', $url);
		$this->unibox->xml->add_text($si_descr, true, $trl_args);
		$this->unibox->xml->parse_node();
	} // end add_link()

	/**
    * finishes and displays the message
    * 
    */
    public function display()
    {
        $this->unibox->xml->parse_node();

        if ($this->delete_content)
            $this->unibox->session->env->system->state = 'CONTENT_PROCESSED';

        $this->unibox->xml->restore();
    } // end display()
    
} // end class ub_message

?>