<?php

/**
*
* uniBox 2.0 (winterspring)\n
* (c) Media Soma GbR
*
* 0.1   12.06.2005  pr      1st release
* 1.0   07.09.2006  jn      flagged as final
*
*/

class ub_email
{
    /**
    * class version
    * 
    */
    const version = '0.1.0';

    /**
    * unibox framework object
    * 
    */
	protected $unibox;

    /**
    * array of recipients (including names)
    * 
    */
    protected $rcpt = array();

	/**
	* array of recipients (only addresses)
	* 
	*/
    protected $rcpt_addr = array();

	/**
	* array of cc addresses
	* 
	*/
	protected $cc = array();
	
	/**
	* array of cc addresses
	* 
	*/
	protected $bcc = array();

	/**
	* subject string
	* 
	*/
	protected $subject;

	/**
	* body string
	* 
	*/
	protected $body;

	/**
	* mail priority
	* 
	*/
	protected $priority;

	/**
	* content-type string
	* 
	*/
	protected $content_type;

    /**
    * returns class version
    * 
    * @return       version-number (float)
    */
    public static function get_version()
    {
        return ub_config::version;
    } // end get_version()

    /**
    * class constructor - sets default values
    *
    */
	public function __construct()
	{
	 	$this->unibox = ub_unibox::get_instance();
	 	$this->priority = $this->unibox->config->system->mail_default_priority;
		$this->content_type = $this->unibox->config->system->mail_default_content_type;
		$this->ubc = new ub_ubc();
	}

    /**
    * loads a mail template
    *
    * @param        $template_ident         ident of template to be loaded (string)
    * @param        $lang_ident             what language of the template should be loaded
    */
	public function load_template($template_ident, $lang_ident)
	{
		$sql_string  = 'SELECT
						  template_subject,
						  template_body
						FROM
						  sys_email_templates
						WHERE
						  template_container_ident = \''.$template_ident.'\'
						  AND
						  lang_ident = \''.$lang_ident.'\'';
		$result = $this->unibox->db->query($sql_string, 'failed to load email template');
		if ($result->num_rows() == 1)
		{
			list($template_subject, $template_body) = $result->fetch_row();
			$result->free();
			$this->subject = $template_subject;
			$this->body = $template_body;
		}
		else
            throw new ub_exception_runtime('failed to load email template \''.$template_ident.'\' for language \''.$lang_ident.'\'');
		
		$this->set_content_type('text/html');
	}

	public function set_replacement($ident, $value)
	{
		$this->ubc->set_replacement($ident, $value);
	}
    
	/**
    * adds a recipient
    * 
    * @param        $addr           email address (string)
    * @param        $name           recipients name (string)
    */
	public function set_rcpt($addr, $name = '')
	{
        if (trim($name) == '')
            $this->rcpt[] = $addr;
        else
            $this->rcpt[] = '"'.$name.'" <'.$addr.'>';
		$this->rcpt_addr[] = $addr;
	} // end set_rcpt()

	/**
	* adds a cc address
	* 
	* @param      $addr           email address (string)
	*/
	public function set_cc($addr)
	{
		$this->cc[] = $addr;
	} // end set_cc()
		
	/**
	* adds a bcc address
	* 
	* @param      $addr           email address (string)
	*/
	public function set_bcc($addr)
	{
		$this->bcc[] = $addr;
	} // end set_bcc()

	/**
	* sets and parses the subject string
	*
	* @param       $subject        subject (string)
	*/
	public function set_subject($subject)
	{
		$this->subject = $subject;
	} // end set_subject();
	
	/**
	* sets and parses the body string
	*
	* @param       $body           body (string)
	*/
	public function set_body($body)
	{
		$this->body = $body;
	} // end set_body();
	
	/**
	* sets the mail priority
	*
	* @param	  $priority       mail priority (int)
	*/
	public function set_priority($priority)
	{
		$this->priority = $priority;
	} // end set_priority()
		
	/**
	* sets the content type
	*
	* @param       $content_type   content type (string)
	*/
	public function set_content_type($content_type)
	{
		$this->content_type = strtolower($content_type);
	} // end set_content_type()

    /**
    * sets the content type
    *
    * @return       result (bool)
    */
	public function send($sender_email = null, $reply_email = null, $skip_ubc_transformation = false)
	{
		// check if at least one recipient has been set
		if (count($this->rcpt) < 1)
			return false;

		if ($sender_email === null)
			$sender_email = $this->unibox->config->system->mail_from;
		
		if ($reply_email === null)
			$reply_email = $this->unibox->config->system->mail_reply_to;
			
		// transform ubc to html, if content-type is text/html
        if (!$skip_ubc_transformation && $this->content_type == 'text/html')
        	$this->body = ub_functions::unescape($this->ubc->ubc2html($this->body));

		// build mail headers
        $headers = 'From: '.$sender_email."\n";
		//$headers .= 'To: '.implode(', ', $this->rcpt)."\n";
		if (count($this->cc) > 0)
			$headers .= 'Cc: '.implode(', ', $this->cc)."\n";
		if (count($this->bcc) > 0)
			$headers .= 'Bcc: '.implode(', ', $this->bcc)."\n";
		$headers .= 'Reply-To: '.$reply_email."\n";
		$headers .= 'X-Priority: '.$this->priority."\n";
		$headers .= 'X-Mailer: uniBox CMS v2.0 (Mailhandler v'.self::version.")\n";
		$headers .= 'MIME-Version: 1.0'."\n";
		$headers .= 'Content-Type: '.$this->content_type.'; charset=utf-8';

		// DEBUG
		if (DEBUG == 3)
        {
    		echo 'Headers: '.$headers.'<br/>';
    		echo 'Recipients: '.implode(', ', $this->rcpt).'<br/>';
    		echo 'Subject: '.$this->subject.'<br/>';
    		echo 'Body: '.ub_functions::strip_cr($this->body).'<br/><br/><br/>';
        }

		// decide what dispatch method to use
		switch ($this->unibox->config->system->mail_dispatch_method)
		{
			// TODO: implement smart mailer class
			case 'sendmail':
				return @mail(implode(', ', $this->rcpt), $this->subject, ub_functions::strip_cr($this->body), $headers);
			default:
				return false;
		}			
	}
}

?>
