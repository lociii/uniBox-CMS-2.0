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
        if (is_null(self::$instance))
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
        $email->set_rcpt($this->unibox->config->system->contact_rcpt_email);
        $email->set_subject('Kontaktgesuch auf '.$this->unibox->config->system->page_url);
        $email->load_template('contact', 'de');

        $email->set_replacement('name', $this->unibox->session->env->form->contact->data->name);
        $email->set_replacement('email', $this->unibox->session->env->form->contact->data->email);
        $email->set_replacement('phone', $this->unibox->session->env->form->contact->data->phone);
        $email->set_replacement('notes', ub_functions::unescape($this->unibox->session->env->form->contact->data->notes));

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
        $this->unibox->xml->goto('location');
        $this->unibox->xml->add_node('component');
        $this->unibox->xml->add_value('value', 'TRL_CONTACT', true);
        $this->unibox->xml->parse_node();
        $this->unibox->xml->restore();

        $form = ub_form_creator::get_instance();
        $form->begin_fieldset('TRL_CONTACT');
        $form->text('name', 'TRL_NAME', '', 30);
        $form->set_condition(CHECK_NOTEMPTY);
        $form->text('email', 'TRL_EMAIL', '', 30);
        $form->set_condition(CHECK_NOTEMPTY);
        $form->set_condition(CHECK_EMAIL);
        $form->text('phone', 'TRL_PHONE', '', 30);
        $form->end_fieldset();

        $form->begin_fieldset('TRL_MISC');
        $form->textarea('notes', 'TRL_NOTES', '', 30, 8);
        $form->end_fieldset();
        $form->begin_buttonset();
        $form->submit('TRL_SEND_UCASE');
        $form->cancel('TRL_CANCEL_UCASE', $this->unibox->session->env->system->default_alias);
        $form->end_buttonset();
        $form->end_form();
    }
}

?>