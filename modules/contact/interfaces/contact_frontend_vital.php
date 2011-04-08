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

    public function contact_process()
    {
        $email = new ub_email();
        $email->set_rcpt($this->unibox->config->system->contact_rcpt_email);
        $email->set_subject('Kontaktgesuch auf '.$this->unibox->config->system->page_url);
        $email->load_template('contact', 'de');

        $email->set_replacement('title', $this->unibox->session->env->form->contact->data->title);
        $email->set_replacement('family_name', $this->unibox->session->env->form->contact->data->family_name);
        $email->set_replacement('first_name', $this->unibox->session->env->form->contact->data->first_name);
        
        $email->set_replacement('street', $this->unibox->session->env->form->contact->data->street);
        $email->set_replacement('street_number', $this->unibox->session->env->form->contact->data->street_number);
        $email->set_replacement('zip_code', $this->unibox->session->env->form->contact->data->zip_code);
        $email->set_replacement('city', $this->unibox->session->env->form->contact->data->city);
        
        $email->set_replacement('email', $this->unibox->session->env->form->contact->data->email);
        $email->set_replacement('notes', ub_functions::unescape($this->unibox->session->env->form->contact->data->notes));
        $email->set_replacement('phone', ub_functions::unescape($this->unibox->session->env->form->contact->data->phone));

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

    public function contact_order()
    {
        $validator = ub_validator::get_instance();
        return (int)$validator->form_validate('order');
    }

    public function contact_order_form()
    {
        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('title', 'TRL_ORDER_FORM', true);
        $this->unibox->xml->add_value('form_name', 'order');
        $form = ub_form_creator::get_instance();
        $form->begin_form('order', $this->unibox->session->env->alias->name);
    }

    public function contact_order_process()
    {
        $email = new ub_email();
        $email->set_rcpt($this->unibox->config->system->contact_rcpt_email);
        $email->set_subject('Bestellung auf '.$this->unibox->config->system->page_url);
        $email->load_template('order', 'de');

        $email->set_replacement('programm', $this->unibox->session->env->form->order->data->programm);
        $email->set_replacement('zahlweise', $this->unibox->session->env->form->order->data->zahlweise);
        $email->set_replacement('kto_bank', $this->unibox->session->env->form->order->data->kto_bank);
        $email->set_replacement('kto_blz', $this->unibox->session->env->form->order->data->kto_blz);
        $email->set_replacement('kto_nummer', $this->unibox->session->env->form->order->data->kto_nummer);
        $email->set_replacement('kto_inhaber', $this->unibox->session->env->form->order->data->kto_inhaber);
        $email->set_replacement('first_name', $this->unibox->session->env->form->order->data->first_name);
        $email->set_replacement('family_name', $this->unibox->session->env->form->order->data->family_name);
        $email->set_replacement('street', $this->unibox->session->env->form->order->data->street);
        $email->set_replacement('zip', $this->unibox->session->env->form->order->data->zip);
        $email->set_replacement('city', $this->unibox->session->env->form->order->data->city);
        $email->set_replacement('phone', $this->unibox->session->env->form->order->data->phone);
        $email->set_replacement('fax', $this->unibox->session->env->form->order->data->fax);
        $email->set_replacement('email', $this->unibox->session->env->form->order->data->email);
        $email->set_replacement('notes', ub_functions::unescape($this->unibox->session->env->form->order->data->notes));
        $email->set_replacement('contact', $this->unibox->session->env->form->order->data->contact);
        // reset contact form
        ub_form_creator::reset('contact');
        if ($email->send())
        {
            $msg = new ub_message(MSG_SUCCESS);
            $msg->add_text('TRL_ORDER_SUCCESSFUL');
            $msg->display();
        }
        else
        {
            $msg = new ub_message(MSG_ERROR);
            $msg->add_text('TRL_ORDER_FAILED');
            $msg->display();
        }
    }

    public function form_contact()
    {
        $this->unibox->config->load_config('articles');
        $this->unibox->xml->goto('location');
        $this->unibox->xml->add_node('component');
        $this->unibox->xml->add_value('value', 'TRL_CONTACT_FORM', true);
        $this->unibox->xml->parse_node();
        $this->unibox->xml->restore();
        
        
        $form = ub_form_creator::get_instance();
        $form->text('family_name', 'TRL_FAMILY_NAME', '', 30);
        $form->set_condition(CHECK_NOTEMPTY, 'TRL_ERR_ENTER_NAME');
        $form->text('email', 'TRL_EMAIL', '', 30);
        $form->set_condition(CHECK_NOTEMPTY, 'TRL_ERR_ENTER_EMAIL');
        $form->set_condition(CHECK_EMAIL, 'TRL_ERR_ENTER_EMAIL');
        $form->text('phone', 'TRL_PHONE', '', 30);
        $form->set_condition(CHECK_NOTEMPTY, 'TRL_ERR_ENTER_PHONE');
        $form->textarea('notes', 'TRL_NOTES', '', 30, 8);
        $form->set_condition(CHECK_NOTEMPTY, 'TRL_ERR_ENTER_NOTES');
        $form->begin_buttonset();
        $form->submit('TRL_SEND_UCASE');
        $form->cancel('TRL_CANCEL_UCASE', $this->unibox->session->env->system->default_alias);
        $form->end_buttonset();
        $sql_string  = 'SELECT
                          article_message
                        FROM
                          '.TABLE_DATA_ARTICLES.'
                        WHERE
                          lang_ident = \''.$this->unibox->session->lang_ident.'\'
                          AND
                          articles_container_id = 11';
        $result = $this->unibox->db->query($sql_string, 'failed to select text');
        if ($result->num_rows() == 1)
        {
            list($message) = $result->fetch_row();
            $form->plaintext('[br /]'.trim($message));
        }
        $form->end_form();
    }

    public function form_order()
    {
        $this->unibox->xml->goto('location');
        $this->unibox->xml->add_node('component');
        $this->unibox->xml->add_value('value', 'TRL_ORDER', true);
        $this->unibox->xml->parse_node();
        $this->unibox->xml->restore();

        $form = ub_form_creator::get_instance();
        
        $form->plaintext('Gerne können Sie ihre Bestellung auch direkt unter [email href="info@vitale-ernaehrung.de"]info@vitale-ernaehrung.de[/email] aufgeben.[br /][br /]');
        
        $form->begin_radio('programm', 'TRL_FORM_BESTELLUNG_PROGRAMM');
        $form->add_option('standard', 'TRL_FORM_BESTELLUNG_ERFOLGSPROGRAMM');
        $form->add_option('vollwert', 'TRL_FORM_BESTELLUNG_VOLLWERT');
        $form->add_option('kochbuch', 'TRL_FORM_BESTELLUNG_KOCHBUCH');
        $form->end_radio();
        $form->set_condition(CHECK_NOTEMPTY);

        $form->plaintext('[br /][strong]Wenn Sie direkt bestellen möchten, überweisen Sie uns bitte vorab den Bestellbetrag auf das Konto 210 167 63 der Sparkasse Neunkirchen BLZ.: 592 520 46.[br /]Gerne können Sie Ihre Bestellung auch per Nachnahme oder per Lastschrift zahlen.[/strong][br /][br /]');

        $form->begin_radio('zahlweise', 'TRL_FORM_BESTELLUNG_ZAHLWEISE');
        $form->add_option('vorauskasse', 'TRL_FORM_BESTELLUNG_VORAUSKASSE');
        $form->add_option('nachnahme', 'TRL_FORM_BESTELLUNG_NACHNAHME');
        $form->add_option('lastschrift', 'TRL_FORM_BESTELLUNG_LASTSCHRIFT');
        $form->end_radio();
        $form->set_condition(CHECK_NOTEMPTY);

        $form->begin_fieldset('TRL_FORM_BESTELLUNG_KONTO_DETAILS');
        $form->text('kto_bank', 'TRL_BANK', '', 30);
        $form->text('kto_blz', 'TRL_BLZ', '', 30);
        $form->text('kto_nummer', 'TRL_KONTONUMMER', '', 30);
        $form->text('kto_inhaber', 'TRL_KONTOINHABER', '', 30);
        $form->end_fieldset();

        $form->plaintext('[br /][strong]Für Auslandsüberweisungen beachten Sie bitte unsere internationale Kontonummer:[/strong][br /]IBAN: DE76 592 520 46 0021 0167 63[br /]SWIFT: SALADE51NKS[br /][strong]Auslandsporto:[/strong][br /]für Programm: € 8,00[br /]für Buch: € 4,50[br /][br /]');

        $form->text('first_name', 'TRL_FIRST_NAME', '', 30);
        $form->set_condition(CHECK_NOTEMPTY);
        $form->text('family_name', 'TRL_FAMILY_NAME', '', 30);
        $form->set_condition(CHECK_NOTEMPTY);
        $form->text('street', 'TRL_STREET', '', 30);
        $form->set_condition(CHECK_NOTEMPTY);
        $form->text('zip', 'TRL_ZIPCODE', '', 10);
        $form->set_condition(CHECK_NOTEMPTY);
        $form->text('city', 'TRL_CITY', '', 30);
        $form->set_condition(CHECK_NOTEMPTY);
        $form->text('phone', 'TRL_PHONE', '', 30);
        $form->text('fax', 'TRL_FAX', '', 30);
        $form->text('email', 'TRL_EMAIL', '', 30);
        $form->set_condition(CHECK_NOTEMPTY);
        $form->set_condition(CHECK_EMAIL);
        $form->textarea('notes', 'TRL_NOTES', '', 30, 8);
        
        $form->plaintext('[br /]');
        
        $form->begin_radio('contact', 'TRL_FORM_BESTELLUNG_HOW_TO_CONTACT');
        $form->add_option('email', 'TRL_FORM_BESTELLUNG_CONTACT_BY_EMAIL');
        $form->add_option('phone', 'TRL_FORM_BESTELLUNG_CONTACT_BY_PHONE');
        $form->end_radio();

        $form->plaintext('[br /]');

        $form->begin_buttonset();
        $form->submit('TRL_SEND_UCASE');
        $form->cancel('TRL_CANCEL_UCASE', $this->unibox->session->env->system->default_alias);
        $form->end_buttonset();
        $form->end_form();
    }

}

?>