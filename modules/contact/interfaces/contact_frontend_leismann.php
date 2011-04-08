<?php

/**
*
* uniBox 2.0 (winterspring)
* (c) Media Soma GbR
*
*/

class ub_contact_frontend_interface
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
        return ub_contact_frontend_interface::version;
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
            self::$instance = new ub_contact_frontend_interface;
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

    public function contact_process()
    {
        $email = new ub_email();
        $email->load_template('contact', 'de');
        $email->set_rcpt($this->unibox->config->system->contact_rcpt_email);

        $email->set_replacement('family_name', $this->unibox->session->env->form->contact->data->family_name);
        $email->set_replacement('email', $this->unibox->session->env->form->contact->data->email);
        $email->set_replacement('phone', ub_functions::unescape($this->unibox->session->env->form->contact->data->phone));
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
        $form = ub_form_creator::get_instance();
        $form->text('family_name', 'TRL_FAMILY_NAME', '', 30);
        $form->set_condition(CHECK_NOTEMPTY);
        $form->text('email', 'TRL_EMAIL', '', 30);
        $form->set_condition(CHECK_NOTEMPTY);
        $form->set_condition(CHECK_EMAIL);
        $form->text('phone', 'TRL_PHONE', '', 30);
        $form->set_condition(CHECK_NOTEMPTY);
        $form->textarea('notes', 'TRL_NOTES', '', 30, 8);
        $form->set_condition(CHECK_NOTEMPTY);
        $form->begin_buttonset();
        $form->submit('TRL_SEND_UCASE');
        $form->cancel('TRL_CANCEL_UCASE', $this->unibox->session->env->system->default_alias);
        $form->end_buttonset();
        $sql_string  = 'SELECT
                          article_message
                        FROM
                          data_articles
                        WHERE
                          lang_ident = \''.$this->unibox->session->lang_ident.'\'
                          AND
                          articles_container_id = 86';
        $result = $this->unibox->db->query($sql_string, 'failed to select text');
        if ($result->num_rows() == 1)
        {
            list($message) = $result->fetch_row();
            $form->plaintext('[br /][br /]'.trim($message));
        }
        $form->end_form();
    }
}

?>