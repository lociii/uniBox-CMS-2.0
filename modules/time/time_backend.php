<?php

/**
*
* uniBox 2.0 (winterspring)
* (c) Media Soma GbR
*
*/

class ub_time_backend
{
    /**
    * $version
    *
    * constant that contains the class version
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
    private static $instance = null;

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
        return ub_time_backend::version;
    }

    /**
    * get_instance()
    *
    * return class instance
    * 
    * @access   public
    * @return   object      object of current class
    */
    public static function get_instance()
    {
        if (self::$instance == null)
            self::$instance = new ub_time_backend;
        return self::$instance;
    }

    /**
    * __construct()
    *
    * @access   public
    */
    public function __construct()
    {
        $this->unibox = ub_unibox::get_instance();
        $this->unibox->config->load_config('time');
    }

    /**
    * welcome()
    *
    * shows the welcome message
    */
    public function welcome()
    {
        $msg = new ub_message(MSG_INFO);
        $msg->add_text('TRL_TIME_WELCOME_TEXT');
        $msg->display();
        return 0;
    }
    
    public function import()
    {
        $validator = ub_validator::get_instance();
        return (int) $validator->form_validate('time_import');
    }
    
    public function import_form()
    {
        $this->unibox->load_template('shared_form_display');
        $this->unibox->xml->add_value('form_name', 'time_import');
        $form = ub_form_creator::get_instance();
        $form->begin_form('time_import', 'time_import');
        $form->begin_fieldset('TRL_GENERAL');
        $form->file('unibox_timezone', 'TRL_UNIBOX_TIMEZONE_ARCHIVE', DIR_TEMP);
        $file_types = array('application/x-tar');
        if (extension_loaded('bz2'))
            $file_types[] = 'application/x-bzip2';
        if (extension_loaded('zlib'))
            $file_types[] = 'application/x-gzip';
        $form->set_condition(CHECK_FILE_EXTENSION, $file_types);
        $form->end_fieldset();
        $form->begin_buttonset();
        $form->submit('TRL_IMPORT_UCASE');
        $form->cancel('TRL_CANCEL_UCASE', 'time_welcome');
        $form->end_buttonset();
        $form->end_form();
    }

    public function import_process()
    {
        // begin transaction with disabled foreign key checks
        $this->unibox->db->begin_transaction(true);

        try
        {
            // delete ALL existing data
            $sql_string = 'DELETE FROM sys_countries';
            if (!$this->unibox->db->query($sql_string, 'failed to delete from country table'))
                throw new ub_exception_transaction('failed to delete from country table');
            $sql_string = 'DELETE FROM sys_timezones';
            if (!$this->unibox->db->query($sql_string, 'failed to delete from timezone table'))
                throw new ub_exception_transaction('failed to delete from timezone table');
            $sql_string = 'DELETE FROM sys_timezone_definitions';
            if (!$this->unibox->db->query($sql_string, 'failed to delete from timezone definitions table'))
                throw new ub_exception_transaction('failed to delete from timezone definitions table');
            $sql_string = 'DELETE FROM sys_timezone_rules';
            if (!$this->unibox->db->query($sql_string, 'failed to delete from timezone rules table'))
                throw new ub_exception_transaction('failed to delete from timezone rules table');
            $sql_string = 'DELETE FROM sys_timezone_continents';
            if (!$this->unibox->db->query($sql_string, 'failed to delete from timezone rules table'))
                throw new ub_exception_transaction('failed to delete from timezone rules table');
            $continents = array();
            // initialize continent translation matrix
            $si_continents = array('africa'        => 'TRL_CONTINENT_NAME_AFRICA',
                                   'america'       => 'TRL_CONTINENT_NAME_AMERICA',
                                   'antarctica'    => 'TRL_CONTINENT_NAME_ANTARCTICA',
                                   'arctic'        => 'TRL_CONTINENT_NAME_ARCTIC',
                                   'asia'          => 'TRL_CONTINENT_NAME_ASIA',
                                   'atlantic'      => 'TRL_CONTINENT_NAME_ATLANTIC_OCEAN',
                                   'australia'     => 'TRL_CONTINENT_NAME_AUSTRALIA',
                                   'europe'        => 'TRL_CONTINENT_NAME_EUROPE',
                                   'indian'        => 'TRL_CONTINENT_NAME_INDIAN_OCEAN',
                                   'pacific'       => 'TRL_CONTINENT_NAME_PACIFIC_OCEAN');

            // open passed file and load xml
            if (!($tar = ub_functions::tar_get_content(DIR_BASE.DIR_TEMP.$this->unibox->session->env->form->time_import->data->unibox_timezone['tmp_name'], $this->unibox->session->env->form->time_import->data->unibox_timezone['type'])))
                throw new ub_exception_transaction('failed to open file');

            foreach ($tar as $file => $content)
                if (!($tar->$file = @simplexml_load_string($content)))
                    throw new ub_exception_transaction('failed to load xml: '.$file);

            // process countries
            $sql_string  = 'INSERT INTO
                              sys_countries
                              (country_ident, si_name, latitude, longitude)
                            VALUES';
            foreach ($tar->{'countries.xml'}->country as $country)
            {
                $attributes = $country->attributes();
                $si_name = 'TRL_COUNTRY_NAME_'.strtoupper($attributes['ident']);
                $this->unibox->insert_string_ident($si_name);
                $sql_string .= '(\''.$this->unibox->db->cleanup($attributes['ident']).'\', \''.$this->unibox->db->cleanup($si_name).'\', '.((isset($country->latitude)) ? $country->latitude : '0').', '.((isset($country->longitude)) ? $country->longitude : '0').'), ';
            }
            if (!$this->unibox->db->query(substr($sql_string, 0, -2), 'failed to insert countries') || $this->unibox->db->affected_rows() == 0)
                throw new ub_exception_transaction('failed to insert countries');

            // process timezones and calculate local rules
            $added_rules = array();
            foreach ($tar->{'zones.xml'}->zone as $zone)
            {
                @set_time_limit(30);
                if (empty($zone->country_ident))
                    continue;
                $attributes = $zone->attributes();
                $zone_ident = $attributes['ident'];

                // process zone name
                $splitted_zone_ident = explode('/', $zone_ident);
                $count = count($splitted_zone_ident);

                // insert continent if not already done
                if (!isset($continents[$splitted_zone_ident[0]]))
                {
                    $this->unibox->insert_string_ident($si_continents[$splitted_zone_ident[0]]);
                    $sql_string  = 'INSERT INTO
                                      sys_timezone_continents
                                    SET
                                      si_name = \''.$si_continents[$splitted_zone_ident[0]].'\'';
                    if (!$this->unibox->db->query($sql_string, 'failed to insert continent: '.$splitted_zone_ident[0]) || $this->unibox->db->affected_rows() != 1 || (($continents[$splitted_zone_ident[0]] = $this->unibox->db->last_insert_id()) === false))
                        throw new ub_exception_transaction('failed to insert continent: '.$splitted_zone_ident[0]);
                }

                $zone_area = 'TRL_AREA_NAME_'.strtoupper($splitted_zone_ident[$count - 1]);
                $this->unibox->insert_string_ident($zone_area);

                $sql_string  = 'INSERT INTO
                                  sys_timezones
                                SET
                                  zone_ident = \''.$zone_ident.'\',
                                  country_ident = \''.$this->unibox->db->cleanup($zone->country_ident).'\',
                                  continent_id = '.$continents[$splitted_zone_ident[0]].',
                                  si_area = \''.$zone_area.'\'';
                if (!$this->unibox->db->query($sql_string, 'failed to insert time zone: '.$zone_ident) || $this->unibox->db->affected_rows() != 1)
                    throw new ub_exception_transaction('failed to insert time zone: '.$zone_ident);

                // collect zone definitions
                $sql_string_data_zone = '';
                foreach ($zone->definition as $zone_definition)
                {
                    $attributes = $zone_definition->attributes();
                    $sql_string_data_zone .= '(\''.$this->unibox->db->cleanup($zone_ident).'\',
                                             '.$attributes['begin'].',
                                             '.$zone_definition->begin_month.',
                                             '.$zone_definition->begin_dayofmonth.',
                                             '.$attributes['end'].',
                                             '.$zone_definition->end_month.',
                                             '.$zone_definition->end_dayofmonth.',
                                             '.$zone_definition->offset.',
                                             '.(isset($zone_definition->rule_name) ? '\''.$this->unibox->db->cleanup($zone_definition->rule_name).'\'' : 'null').'
                                             ), ';
    
                    // adjust rules for current zone
                    if (isset($zone_definition->rule_name) && !in_array($zone_definition->rule_name.':'.$zone_definition->offset, $added_rules))
                    {
                        $added_rules[] = $zone_definition->rule_name.':'.$zone_definition->offset;
                        $rule = $tar->{'rules.xml'}->xpath('//rule[@ident="'.$zone_definition->rule_name.'"]/definition');
                        if (is_array($rule))
                        {
                            // collect rule definitions
                            $sql_string_data_rule = '';
                            foreach ($rule as $rule_definition)
                            {
                                $attributes = $rule_definition->attributes();
                                list($rule_definition->processed_time, $rule_definition->utc_correction) = $this->olson_parse_at_value($rule_definition->time, $zone_definition->offset, $rule_definition->offset);
                                $sql_string_data_rule .= '(\''.$this->unibox->db->cleanup($zone_definition->rule_name).'\', '.$zone_definition->offset.', '.$rule_definition->offset.', '.$attributes['year'].', '.$attributes['endless'].', '.$attributes['month'].', '.$rule_definition->startday.', '.$rule_definition->weekday.', \''.$rule_definition->processed_time.'\', '.$rule_definition->utc_correction.'), ';
                            }
                            // insert rule definitions
                            $sql_string  = 'INSERT INTO
                                              sys_timezone_rules
                                                (rule_ident, utc_offset, dst_offset, year, endless, month, startday, weekday, time, utc_correction)
                                              VALUES '.substr($sql_string_data_rule, 0, -2);
                            if (!$this->unibox->db->query($sql_string, 'failed to insert time zone rule: '.$zone_ident.' - '.$zone_definition->rule_name) || $this->unibox->db->affected_rows() == 0)
                                throw new ub_exception_transaction('failed to insert time zone rule: '.$zone_ident.' - '.$zone_definition->rule_name);
                        }
                    }
                }

                // insert zone definitions
                $sql_string  = 'INSERT INTO
                                  sys_timezone_definitions
                                    (zone_ident, begin_year, begin_month, begin_dayofmonth, end_year, end_month, end_dayofmonth, utc_offset, rule_ident)
                                  VALUES '.substr($sql_string_data_zone, 0, -2);
                if (!$this->unibox->db->query($sql_string, 'failed to insert time zone definition: '.$zone_ident) || $this->unibox->db->affected_rows() == 0)
                    throw new ub_exception_transaction('failed to insert time zone definition: '.$zone_ident);
            }

            if (($result = $this->unibox->db->tools->check_integrity()) !== true)
                throw new ub_exception_transaction('used timezone doesn\'t exist anymore', 'TRL_ERR_IMPORT_TIMEZONE_USED_TIMEZONE_NOT_DEFINED');
        }
        catch (ub_exception_transaction $exception)
        {
            $exception->process('TRL_IMPORT_FAILED');
            ub_form_creator::reset('time_import');
            return;
        }

        $this->unibox->db->commit();
        $msg = new ub_message(MSG_SUCCESS);
        $msg->add_text('TRL_IMPORT_SUCCESS');
        $msg->display();
    }

    /**
    * parses at values given by olson files
    * 
    * possible formats as described in `man zic` 
    *   2        time in hours
    *   2:00     time in hours and minutes
    *   15:00    24-hour format time (for times after noon)
    *   1:28:14  time in hours, minutes, and seconds
    * 
    * according to `man zic`
    * Any of these forms may be followed by the letter w if the given
    * time is local "wall clock" time, s if the given time is local
    * "standard" time, or u (or g or z) if the given time is universal
    * time; in the absence of an indicator, wall clock time is assumed.
    * 
    * return value format
    * 'startday': day of the month or -1 for backwards
    * 'weekday' : numeric representation of the day of the week (see php:date) or -1 for no specific weekday
    * 
    * @param        value to parse      string
    * @return       parsed value (array)
    */
    protected function olson_parse_at_value($at, $utc_offset, $dst_offset = 0)
    {
        // initialize utc correction
        $utc_correction = 0;

        // search for time signature
        if (preg_match('/[ugzs]$/', $at, $matches))
        {
            $sig = $matches[0];
            // cut off time signature
            $at = substr($at, 0, strlen($at)-1);
        }

        // time is already utc - return it
        if (isset($sig) && ($sig === 'u' ||$sig === 'g' || $sig === 'z'))
            return array($at, $utc_correction);

        // once again - ignore seconds
        list($hours, $minutes) = explode(':', $at);

        // 'local wall clock'
        $dst_offset_hours = floor($dst_offset / 60);
        $dst_offset_minutes = ($dst_offset % 60);
        if (!isset($sig) || $sig === 'w')
        {
            // substract dst offset
            $minutes = $minutes - $dst_offset_minutes;
            if ($minutes < 0)
            {
                $hours--;
                $minutes = abs($minutes);
            }
            elseif ($minutes > 59)
            {
                $hours++;
                $minutes = $minutes - 60;
            }

            $hours = $hours - $dst_offset_hours;
            if ($hours < 0)
            {
                $utc_correction--;
                $hours = 24 + $hours;
            }
            elseif ($hours > 23)
            {
                $utc_correction++;
                $hours = $hours - 24;
            }

            // set time signature to local standard time (forces utc processing)
            $sig = 's';
        }

        // compute utc
        $utc_offset_hours = floor($utc_offset / 60);
        $utc_offset_minutes = ($utc_offset % 60);
        if (isset($sig) && $sig === 's')
        {
            // substract utc offset
            $minutes = $minutes - $utc_offset_minutes;
            if ($minutes < 0)
            {
                $hours--;
                $minutes = abs($minutes);
            }
            elseif ($minutes > 59)
            {
                $hours++;
                $minutes = $minutes - 60;
            }

            $hours = $hours - $utc_offset_hours;
            if ($hours < 0)
            {
                $utc_correction--;
                $hours = 24 + $hours;
            }
            elseif ($hours > 23)
            {
                $utc_correction++;
                $hours = $hours - 24;
            }

            // return utc
            return array(sprintf('%02d:%02d', $hours, $minutes), $utc_correction);
        }

        // no rule has matched - error
        throw new ub_exception();
    }

}

?>