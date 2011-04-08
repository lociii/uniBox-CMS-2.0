<?php

/**
*
* uniBox 2.0 (winterspring)
* (c) Media Soma GbR
*
*/

class ub_contact_frontend
{
    /**
    * $version
    *
    * variable that contains the class version
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
        return ub_contact_frontend::version;
    }

    /**
    * get_instance()
    *
    * return class instance
    * 
    * @access   public
    * @return   object      ub_contact_frontend object
    */
    public static function get_instance()
    {
        if (self::$instance === null)
            self::$instance = new ub_contact_frontend;
        return self::$instance;
    }

    /**
    * __construct()
    *
    * session constructor - gets called everytime the objects get instantiated
    * 
    * @access   public
    */
    public function __construct()
    {
        $this->unibox = ub_unibox::get_instance();
        $this->unibox->config->load_config('contact');
    }

    public function contact()
    {
        $validator = ub_validator::get_instance();
        return (int)$validator->form_validate('contact');
    }

    public function contact_form()
    {
        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('title', 'TRL_CONTACT_FORM', true);
        $this->unibox->xml->add_value('form_name', 'contact');
        $form = ub_form_creator::get_instance();
        $form->begin_form('contact', $this->unibox->session->env->alias->name);
    }

    public function contact_process()
    {
        $email = new ub_email();
        $email->set_content_type('text/html');
        $email->set_rcpt($this->unibox->config->system->contact_rcpt_email);
        $email->set_subject($this->unibox->translate('TRL_CONTACT_EMAIL_SUBJECT', array($this->unibox->config->system->page_url)));

		// generate body
		$ubc_str = '';
		foreach ($this->unibox->session->env->form->contact->data as $key => $value)
			if ($key != 'submit_id'
				&&
				$key != $this->unibox->session->env->form->contact->spec->name.'_form_antispam'
				&&
				$this->unibox->session->env->form->contact->spec->elements->$key->input_type != 'hidden'
				&&
				trim($value) != ''
			   )
				$ubc_str .= $this->unibox->session->env->form->contact->spec->elements->$key->label.': '.ub_functions::unescape($value).'[br /]';

		// set email body
		$email->set_body($ubc_str);

        // reset contact form
        ub_form_creator::reset('contact');
        if ($email->send())
        {
            $msg = new ub_message(MSG_SUCCESS);
            $msg->add_text('TRL_CONTACT_SUCCESSFUL');
            $msg->display();
        }
        else
        {
            $msg = new ub_message(MSG_ERROR);
            $msg->add_text('TRL_CONTACT_FAILED');
            $msg->display();
        }
    }

    public function form_contact()
    {
        $this->unibox->config->load_config('articles');
        $this->unibox->add_location('TRL_CONTACT_FORM', null, true);
        $form = ub_form_creator::get_instance();

		if ($this->unibox->config->system->contact_top_article_id != 0)
		{
	        $sql_string  = 'SELECT
	                          article_live_message
	                        FROM
	                          data_articles
	                        WHERE
	                          lang_ident = \''.$this->unibox->session->lang_ident.'\'
	                          AND
	                          articles_container_id = '.$this->unibox->config->system->contact_top_article_id;
	        $result = $this->unibox->db->query($sql_string, 'failed to select top message');
	        if ($result->num_rows() == 1)
	        {
	            list($message) = $result->fetch_row();
	            $this->unibox->xml->add_node('input');
	            $this->unibox->xml->set_attribute('type', 'plaintext');
	            $this->unibox->xml->import_xml(trim($message));
	            $this->unibox->xml->parse_node();
	        }
		}

        $form->begin_fieldset('TRL_CONTACT_DATA');
        $form->text('name', 'TRL_NAME', '', 30);
        $form->set_condition(CHECK_NOTEMPTY);
        $form->text('email', 'TRL_EMAIL', '', 30);
        $form->set_condition(CHECK_NOTEMPTY);
        $form->set_condition(CHECK_EMAIL);
        $form->text('phone', 'TRL_PHONE', '', 30);
        $form->end_fieldset();

        $form->begin_fieldset('TRL_MISC');
        $form->textarea('notes', 'TRL_NOTES', '', 30, 8);
        $form->set_condition(CHECK_NOTEMPTY);
        $form->end_fieldset();

		// secure form
		$form->secure();

        $form->begin_buttonset();
        $form->submit('TRL_SEND_UCASE');
        $form->cancel('TRL_CANCEL_UCASE', $this->unibox->session->env->system->default_alias);
        $form->end_buttonset();

		if ($this->unibox->config->system->contact_bottom_article_id != 0)
		{
	        $sql_string  = 'SELECT
	                          article_live_message
	                        FROM
	                          data_articles
	                        WHERE
	                          lang_ident = \''.$this->unibox->session->lang_ident.'\'
	                          AND
	                          articles_container_id = '.$this->unibox->config->system->contact_bottom_article_id;
	        $result = $this->unibox->db->query($sql_string, 'failed to select bottom message');
	        if ($result->num_rows() == 1)
	        {
	            list($message) = $result->fetch_row();
	            $this->unibox->xml->add_node('input');
	            $this->unibox->xml->set_attribute('type', 'plaintext');
	            $this->unibox->xml->import_xml(trim($message));
	            $this->unibox->xml->parse_node();
	        }
		}

        $form->end_form();
    }

}

?>