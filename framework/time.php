<?php

/**
*
* uniBox 2.0 (winterspring)
* (c) Media Soma GbR
*
* 0.1       06.09.2006  jn      1st release\n
* 0.2       22.09.2006  jn      added time comparison functions, fixed add/substract
*
*/

class ub_time
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

    protected $time = array();
    protected $type_from = null;
    protected $type_to = null;
    protected $transformed = false;
    protected $compute_localtime = true;

    /**
    * returns class version
    * 
    * @return       version-number (float)
    */
    public static function get_version()
    {
        return ub_time::version;
    }

    public function __construct($type_from, $type_to, $compute_localtime = true)
    {
        $this->unibox = ub_unibox::get_instance();
        $this->type_from = $type_from;
        $this->type_to = $type_to;
        $this->compute_localtime = $compute_localtime;

        $this->time['hours'] = 0;
        $this->time['minutes'] = 0;
        $this->time['seconds'] = 0;
    }

    public function reset()
    {
        $this->time = array();
        $this->time['hours'] = 0;
        $this->time['minutes'] = 0;
        $this->time['seconds'] = 0;
        $this->transformed = false;
    }

    public function now()
    {
        $now = time();
        $this->time['year'] = (int)date('Y', $now);
        $this->time['mon'] = (int)date('m', $now);
        $this->time['mday'] = (int)date('j', $now);
        $this->time['hours'] = (int)date('G', $now);
        $this->time['minutes'] = (int)date('i', $now);
        $this->time['seconds'] = (int)date('s', $now);

        if ($this->type_from == TIME_TYPE_USER)
        {
            $this->transform_process($this->unibox->config->system->server_timezone);
            $this->transform_process($this->unibox->session->timezone, false);
        }
    }

    public function set_year($year)
    {
        $this->time['year'] = $year;
    }
    
    public function set_month($month)
    {
        $this->time['mon'] = $month;
    }

    public function set_day($day)
    {
        $this->time['mday'] = $day;
    }

    public function set_hours($hours)
    {
        $this->time['hours'] = $hours;
    }

    public function set_minutes($minutes)
    {
        $this->time['minutes'] = $minutes;
    }

    public function set_seconds($seconds)
    {
        $this->time['seconds'] = $seconds;
    }

    public function add($minutes)
    {
        $this->time_add($this->time['mday'], $this->time['mon'], $this->time['year'], $this->time['hours'], $this->time['minutes'], $minutes);
        $this->time_temp['seconds'] = $this->time['seconds'];
        $this->time = $this->time_temp;
    }

    public function substract($minutes)
    {
        $this->time_substract($this->time['mday'], $this->time['mon'], $this->time['year'], $this->time['hours'], $this->time['minutes'], $minutes);
        $this->time_temp['seconds'] = $this->time['seconds'];
        $this->time = $this->time_temp;
    }

    public function parse_date($date, $format = null)
    {
        $this->transformed = false;
        if ($format === null)
            if ($this->type_from === TIME_TYPE_USER)
                $format = $this->unibox->session->locale->date_format;
            else
                $format = '%Y-%m-%d';

        $date_pattern = '';
        // builds up date pattern from the given $format, keeping delimiters in place
        $format_tokens = $date_tokens = $date_segments = array();
        if (!preg_match_all('/%([Yymd])([^%])*/', $format, $format_tokens, PREG_SET_ORDER))
            return false;
        foreach ($format_tokens as $format_token)
        {
            if (isset($format_token[2]))
                $delimiter = preg_quote($format_token[2], '/');
            else
                $delimiter = '';
            if ($format_token[1] == 'Y')
                $date_pattern .= '(\d{4})'.$delimiter;
            elseif ($format_token[1] == 'y')
                $date_pattern .= '(\d{2})'.$delimiter;
            else
                $date_pattern .= '(\d{1,2})'.$delimiter;
        }

        // splits up the given $date
        if (!preg_match('/'.$date_pattern.'/i', $date, $date_tokens))
            return false;

        // build date segments array
        $token_count = count($format_tokens);
        for ($i = 0; $i < $token_count; $i++)
            $date_segments[$format_tokens[$i][1]] = $date_tokens[$i+1];

        // check day
        if (isset($date_segments['d']))
            $date_segments['d'] = (int)$date_segments['d'];
        else
            return false;

        // check month
        if (isset($date_segments['m']))
            $date_segments['m'] = (int)$date_segments['m'];
        else
            return false;

        // check year
        if (isset($date_segments['y']))
        {
            if ($date_segments['y'] < 70)
                $date_segments['Y'] = (int)'20'.$date_segments['y'];
            else
                $date_segments['Y'] = (int)'19'.$date_segments['y'];
        }
        elseif (isset($date_segments['Y']))
            $date_segments['Y'] = (int)$date_segments['Y'];
        else
            return false;

        // check if date is valid
        if (!checkdate($date_segments['m'], $date_segments['d'], $date_segments['Y']))
            return false;

        $this->time['mday'] = $date_segments['d'];
        $this->time['mon'] = $date_segments['m'];
        $this->time['year'] = $date_segments['Y'];
        return true;
    }

    public function parse_time($time, $format = null)
    {
        $this->transformed = false;
        if ($format === null)
        {
            if ($this->type_from === TIME_TYPE_USER)
                $format = $this->unibox->session->locale->time_format;
            else
                $format = '%H:%M:%S';
        }

        $date_pattern = '';
        // builds up time pattern from the given $format, keeping delimiters in place
        $format_tokens = $date_tokens = $time_segments = $return = array();
        if (!preg_match_all('/%([HMSp])([^%])*/', $format, $format_tokens, PREG_SET_ORDER))
            return false;
        foreach ($format_tokens as $format_token)
        {
            if (isset($format_token[2]))
                $delimiter = preg_quote($format_token[2], '/');
            else
                $delimiter = '';
            if ($format_token[1] == 'p')
                $date_pattern .= '(am|pm)'.$delimiter;
            else
                $date_pattern .= '(\d{1,2})'.$delimiter;
        } 

        // splits up the given $time
        if (!preg_match('/'.$date_pattern.'/i', $time, $date_tokens))
            return false;

        // build time segments array
        $token_count = count($format_tokens);
        for ($i = 0; $i < $token_count; $i++)
            $time_segments[$format_tokens[$i][1]] = $date_tokens[$i+1];

        // check hours
        if (isset($time_segments['p']) && isset($time_segments['H']))
        {
            if (strtolower($time_segments['p']) == 'am' && $time_segments['H'] == 12)
                $time_segments['H'] = 0;
            elseif (strtolower($time_segments['p']) == 'pm' && $time_segments['H'] != 12)
                $time_segments['H'] = (int)$time_segments['H'] + 12;
            else
                $time_segments['H'] = $time_segments['H'];
        }
        elseif (isset($time_segments['H']))
            $time_segments['H'] = (int)$time_segments['H'];
        else
            return false;

        // check minutes
        if (isset($time_segments['M']))
            $time_segments['M'] = (int)$time_segments['M'];
        else
            return false;

        // check seconds
        if (isset($time_segments['S']))
            $time_segments['S'] = (int)$time_segments['S'];
        else
            $time_segments['S'] = 0;

        // check if time is valid
        if (($time_segments['H'] < 0 || $time_segments['H'] > 23) || ($time_segments['M'] < 0 || $time_segments['M'] > 59) || ($time_segments['S'] < 0 || $time_segments['S'] > 59))
            return false;

        $this->time['hours'] = $time_segments['H'];
        $this->time['minutes'] = $time_segments['M'];
        $this->time['seconds'] = $time_segments['S'];

        return true;
    }

    public function parse_datetime($datetime, $date_format = null, $time_format = null)
    {
        if ($date_format === null)
        {
            if ($this->type_from === TIME_TYPE_USER)
                $format = $this->unibox->session->locale->date_format;
            else
                $format = '%Y-%m-%d';
        }

        if ($time_format === null)
        {
            if ($this->type_from === TIME_TYPE_USER)
                $format = $this->unibox->session->locale->time_format;
            else
                $format = '%H:%M:%S';
        }

        if (!$this->parse_date($datetime, $date_format) || !$this->parse_time($datetime, $time_format))
            return false;
        return true;
    }

    public function parse_timestamp($timestamp)
    {
        $this->time['year'] = (int)date('Y', $timestamp);
        $this->time['mon'] = (int)date('m', $timestamp);
        $this->time['mday'] = (int)date('j', $timestamp);
        $this->time['hours'] = (int)date('G', $timestamp);
        $this->time['minutes'] = (int)date('i', $timestamp);
        $this->time['seconds'] = (int)date('s', $timestamp);
    }

	public function get_xml(ub_xml &$xml)
	{
		$this->transform();

		// get all languages and locales
		$sql_string  = 'SELECT
						  a.lang_ident,
						  b.locale,
						  b.date_format,
						  b.time_format,
						  b.datetime_format
						FROM
						  sys_languages AS a,
						  sys_locales AS b
						ORDER BY
						  a.lang_ident,
						  b.locale';
		$result = $this->unibox->db->query($sql_string, 'failed to get languages and locales');
		while (list($lang_ident, $locale, $date_format, $time_format, $datetime_format) = $result->fetch_row())
		{
			$xml->add_node('node');
			$xml->set_attribute('lang_ident', $lang_ident);
			$xml->set_attribute('locale', $locale);
			$xml->add_value('date', $this->get_date($date_format));
	        $xml->add_value('time', $this->get_time($time_format));
	        $xml->add_value('datetime', $this->get_datetime($datetime_format));

			$xml->add_value('weekday', $this->get_weekday(), true);
			$xml->add_value('weekday_short', $this->get_weekday_short(), true);
			$xml->add_value('month', $this->get_month(), true);
			$xml->add_value('month_short', $this->get_month_short(), true);

	        if ($word = $this->get_word())
				$xml->add_value('word', $word, true);
			$xml->parse_node();
		}
		if ($timestamp = $this->get_timestamp())
			$xml->add_value('timestamp', $timestamp);
	}

    public function get_array()
    {
        $this->transform();
        return $this->time;
    }

    public function get_timestamp()
    {
        $this->transform();

        $timestamp = mktime($this->time['hours'], $this->time['minutes'], $this->time['seconds'], $this->time['mon'], $this->time['mday'], $this->time['year']);
        if ($timestamp >= 0)
            return $timestamp;
        else
            return false;
    }

    public function get_date($format = null)
    {
        $this->transform();

        if ($format === null)
            if ($this->type_to === TIME_TYPE_USER)
                $format = $this->unibox->session->locale->date_format;
            else
                $format = '%Y-%m-%d';

        return $this->format_string($format, $this->time);
    }

    public function get_word()
    {
        $this->transform();

        // get current time
        $time = new ub_time(TIME_TYPE_SERVER, TIME_TYPE_USER);
        $time->now();
        $today = $time->get_array();

        // days per month
        $days_per_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);

        // check for leap year if in february
        if (ub_functions::year_is_leap($this->time['year']))
            $days_per_month[1] = 29;

        $tomorrow = array('year' => $today['year'], 'mon' => $today['mon'], 'mday' => $today['mday'] + 1);
        $yesterday = array('year' => $today['year'], 'mon' => $today['mon'], 'mday' => $today['mday'] - 1);

        // check if in december and last day of month
        if ($today['mon'] == 12 && $today['mday'] == 31)
            $tomorrow = array('year' => $today['year'] + 1, 'mon' => 1, 'mday' => 1);
        // check if in january and first day of month
        elseif ($today['mon'] == 1 && $today['mday'] == 1)
            $yesterday = array('year' => $today['year'] - 1, 'mon' => 12, 'mday' => 31);
        // check if last of current month
        elseif ($today['mday'] == $days_per_month[$today['mon'] - 1])
            $tomorrow = array('year' => $today['year'], 'mon' => $today['mon'] + 1, 'mday' => 1);
        // check if first of current month
        elseif ($today['mday'] == 1)
            $yesterday = array('year' => $today['year'], 'mon' => $today['mon'] - 1, 'mday' => $days_per_month[$today['mon'] - 2]);

        // check if today
        if ($today['year'] == $this->time['year'] && $today['mon'] == $this->time['mon'] && $today['mday'] == $this->time['mday'])
            return 'TRL_DAY_TODAY_UCASE';
        // check if tomorrow
        elseif ($tomorrow['year'] == $this->time['year'] && $tomorrow['mon'] == $this->time['mon'] && $tomorrow['mday'] == $this->time['mday'])
            return 'TRL_DAY_TOMORROW_UCASE';
        // check if yesterday
        elseif ($yesterday['year'] == $this->time['year'] && $yesterday['mon'] == $this->time['mon'] && $yesterday['mday'] == $this->time['mday'])
            return 'TRL_DAY_YESTERDAY_UCASE';
        else
            return false;
    }

    public function get_weekday()
    {
        switch (ub_functions::calculate_week_day($this->time['mday'], $this->time['mon'], $this->time['year']))
        {
            case 0:
                return 'TRL_WEEKDAY_SUNDAY';
            case 1:
                return 'TRL_WEEKDAY_MONDAY';
            case 2:
                return 'TRL_WEEKDAY_TUESDAY';
            case 3:
                return 'TRL_WEEKDAY_WEDNESDAY';
            case 4:
                return 'TRL_WEEKDAY_THURSDAY';
            case 5:
                return 'TRL_WEEKDAY_FRIDAY';
            case 6:
                return 'TRL_WEEKDAY_SATURDAY';
        }
    }

    public function get_weekday_short()
    {
        switch (ub_functions::calculate_week_day($this->time['mday'], $this->time['mon'], $this->time['year']))
        {
            case 0:
                return 'TRL_WEEKDAY_SUNDAY_SHORT';
            case 1:
                return 'TRL_WEEKDAY_MONDAY_SHORT';
            case 2:
                return 'TRL_WEEKDAY_TUESDAY_SHORT';
            case 3:
                return 'TRL_WEEKDAY_WEDNESDAY_SHORT';
            case 4:
                return 'TRL_WEEKDAY_THURSDAY_SHORT';
            case 5:
                return 'TRL_WEEKDAY_FRIDAY_SHORT';
            case 6:
                return 'TRL_WEEKDAY_SATURDAY_SHORT';
        }
    }

    public function get_month()
    {
        switch ($this->time['mon'])
        {
            case 1:
                return 'TRL_MONTH_JANUARY';
            case 2:
                return 'TRL_MONTH_FEBRUARY';
            case 3:
                return 'TRL_MONTH_MARCH';
            case 4:
                return 'TRL_MONTH_APRIL';
            case 5:
                return 'TRL_MONTH_MAY';
            case 6:
                return 'TRL_MONTH_JUNE';
            case 7:
                return 'TRL_MONTH_JULY';
            case 8:
                return 'TRL_MONTH_AUGUST';
            case 9:
                return 'TRL_MONTH_SEPTEMBER';
            case 10:
                return 'TRL_MONTH_OCTOBER';
            case 11:
                return 'TRL_MONTH_NOVEMBER';
            case 12:
                return 'TRL_MONTH_DECEMBER';
        }
    }

    public function get_month_short()
    {
        switch ($this->time['mon'])
        {
            case 1:
                return 'TRL_MONTH_JANUARY_SHORT';
            case 2:
                return 'TRL_MONTH_FEBRUARY_SHORT';
            case 3:
                return 'TRL_MONTH_MARCH_SHORT';
            case 4:
                return 'TRL_MONTH_APRIL_SHORT';
            case 5:
                return 'TRL_MONTH_MAY_SHORT';
            case 6:
                return 'TRL_MONTH_JUNE_SHORT';
            case 7:
                return 'TRL_MONTH_JULY_SHORT';
            case 8:
                return 'TRL_MONTH_AUGUST_SHORT';
            case 9:
                return 'TRL_MONTH_SEPTEMBER_SHORT';
            case 10:
                return 'TRL_MONTH_OCTOBER_SHORT';
            case 11:
                return 'TRL_MONTH_NOVEMBER_SHORT';
            case 12:
                return 'TRL_MONTH_DECEMBER_SHORT';
        }
    }

    public function get_time($format = null)
    {
        $this->transform();

        if ($format === null)
            if ($this->type_to === TIME_TYPE_USER)
                $format = $this->unibox->session->locale->time_format;
            else
                $format = '%H:%M:%S';

        return $this->format_string($format, $this->time);
    }

    public function get_datetime($format = null)
    {
        $this->transform();

        if ($format === null)
            if ($this->type_to === TIME_TYPE_USER)
                $format = $this->unibox->session->locale->datetime_format;
            else
                $format = '%Y-%m-%d %H:%M:%S';

        return $this->format_string($format, $this->time);
    }

    public function fill_timezone_form(&$form, $timezone)
    {
        $sql_string  = 'SELECT
		                  a.zone_ident,
		                  CONCAT(\'(UTC \', IF (SIGN(g.utc_offset) > -1, \'+\', \'\'), SUBSTRING_INDEX(SEC_TO_TIME(g.utc_offset * 60), \':\', 2), \') \', CONCAT_WS(\' / \', c.string_value, e.string_value, f.string_value)) AS zone_name
		                FROM sys_timezones AS a
		                  INNER JOIN sys_timezone_continents AS b
		                    ON b.continent_id = a.continent_id
		                  INNER JOIN sys_translations AS c
		                    ON
		                    (
		                    c.string_ident = b.si_name
		                    AND
		                    c.lang_ident = \''.$this->unibox->session->lang_ident.'\'
		                    )
		                  INNER JOIN sys_countries AS d
		                    ON d.country_ident = a.country_ident
		                  INNER JOIN sys_translations AS e
		                    ON
		                    (
		                    e.string_ident = d.si_name
		                    AND
		                    e.lang_ident = \''.$this->unibox->session->lang_ident.'\'
		                    )
		                  INNER JOIN sys_translations AS f
		                    ON
		                    (
		                    f.string_ident = a.si_area
		                    AND
		                    f.lang_ident = \''.$this->unibox->session->lang_ident.'\'
		                    )
		                  INNER JOIN sys_timezone_definitions AS g
		                    ON
		                    (
		                    g.zone_ident = a.zone_ident
		                    AND
		                    (
		                    g.begin_year = (SELECT
		                                      begin_year
		                                    FROM
		                                      sys_timezone_definitions
		                                    WHERE
		                                      zone_ident = a.zone_ident
		                                    ORDER BY
		                                      begin_year DESC,
		                                      begin_month DESC,
		                                      begin_dayofmonth DESC
		                                    LIMIT
		                                      0, 1)
		                      OR
		                      (
		                      g.begin_year = 0
		                      AND
		                      g.end_year = 0
		                      )
		                    )
		                    )
		                ORDER BY
		                  g.utc_offset,
		                  c.string_value,
		                  e.string_value,
		                  f.string_value';
		$form->add_option_sql($sql_string, $timezone);
    }

    protected function format_string($format, $date)
    {
    	$format_tokens = array();
        if (!preg_match_all('/%([YymdHMSp])([^%])*/', $format, $format_tokens, PREG_SET_ORDER))
            return false;

        $match = $replace = array();
    
        if (isset($date['year']))
        {
            $match[] = '/%Y/';
            $replace[] = $date['year'];
            $match[] = '/%y/';
            $replace[] = ($date['year'] % 100);
        }

        if (isset($date['mon']))
        {
            $match[] = '/%m/';
            $replace[] = sprintf('%02d', $date['mon']);
        }

        if (isset($date['mday']))
        {
            $match[] = '/%d/';
            $replace[] = sprintf('%02d', $date['mday']);
        }

        if (isset($date['hours']))
        {
            // search for am/pm
            foreach ($format_tokens as $token)
            {
                if ($token[1] == 'p')
                {
                    if ($date['hours'] > 12)
                    {
                        $match[] = '/%p/';
                        $replace[] = 'pm';
                        $date['hours'] = $date['hours'] - 12;
                    }
                    else
                    {
                        $match[] = '/%p/';
                        $replace[] = 'am';
                    }
                }
            }
            $match[] = '/%H/';
            $replace[] = sprintf('%02d', $date['hours']);
        }

        if (isset($date['minutes']))
        {
            $match[] = '/%M/';
            $replace[] = sprintf('%02d', $date['minutes']);
        }

        if (isset($date['seconds']))
        {
            $match[] = '/%S/';
            $replace[] = sprintf('%02d', $date['seconds']);
        }

        return preg_replace($match, $replace, $format);
    }

    protected function transform()
    {
        if ($this->compute_localtime && !$this->transformed)
        {
            // transform input to utc
            switch ($this->type_from)
            {
                case TIME_TYPE_USER:
                    $this->transform_process($this->unibox->session->timezone);
                    break;
                case TIME_TYPE_SERVER:
                    $this->transform_process($this->unibox->config->system->server_timezone);
                    break;
            }

            switch ($this->type_to)
            {
                case TIME_TYPE_USER:
                    $this->transform_process($this->unibox->session->timezone, false);
                    break;
                case TIME_TYPE_SERVER:
                    $this->transform_process($this->unibox->config->system->server_timezone, false);
                    break;
            }
            $this->transformed = true;
        }
    }

    protected function transform_process($zone_ident, $to_utc = true)
    {
        // check for date
        if (!isset($this->time['mday']) || !isset($this->time['mon']) || !isset($this->time['year']))
            return false;

        $this->time_temp = array();

		if (class_exists('DateTime', false) && class_exists('DateTimeZone', false))
		{
			$time = new DateTime();
			if ($to_utc)
			{
				$timezone_from = new DateTimeZone($zone_ident);
				$timezone_to = new DateTimeZone('UTC');
			}
			else
			{
				$timezone_from = new DateTimeZone('UTC');
				$timezone_to = new DateTimeZone($zone_ident);
			}

			$time->setTimezone($timezone_from);
			$time->setDate($this->time['year'], $this->time['mon'], $this->time['mday']);
			$time->setTime($this->time['hours'], $this->time['minutes'], 0);
			$time->setTimezone($timezone_to);
			$time = $time->format('Y-m-d-H-i');
			list($this->time_temp['year'], $this->time_temp['mon'], $this->time_temp['mday'], $this->time_temp['hours'], $this->time_temp['minutes']) = explode('-', $time);
		}
		else
		{
	        // get utc offset and dst-rule
	        $sql_string  = 'SELECT
	                          utc_offset,
	                          rule_ident,
	                          IF ((begin_month > '.$this->time['mon'].'), begin_month - 12, begin_month) AS begin_month_absolute
	                        FROM
	                          sys_timezone_definitions
	                        WHERE
	                          zone_ident = \''.$this->unibox->db->cleanup($zone_ident).'\'
	                          AND
	                          (
	                            (
	                              begin_year = '.$this->time['year'].'
	                              AND
	                              (
	                                (
	                                  begin_month = '.$this->time['mon'].'
	                                  AND
	                                  begin_dayofmonth <= '.$this->time['mday'].'
	                                )
	                                OR
	                                begin_month < '.$this->time['mon'].'
	                              )
	                            )
	                            OR
	                            begin_year < '.$this->time['year'].'
	                          )
	                          AND
	                          (
	                            (
	                              end_year = '.$this->time['year'].'
	                              AND
	                              (
	                                (
	                                  end_month = '.$this->time['mon'].'
	                                  AND
	                                  end_dayofmonth > '.$this->time['mday'].'
	                                )
	                                OR
	                                end_month > '.$this->time['mon'].'
	                              )
	                            )
	                            OR
	                            end_year > '.$this->time['year'].'
	                            OR
	                            end_year = 0
	                          )
	                        ORDER BY
	                          begin_year DESC,
	                          begin_month_absolute DESC,
	                          begin_dayofmonth DESC
	                        LIMIT
	                          0, 1';
	        if (!($result = $this->unibox->db->query($sql_string, 'failed to get timezone utc offset')) || $result->num_rows() != 1)
	        	throw new ub_exception_runtime('no applicable timezone definition found for date \''.$this->time['year'].'-'.$this->time['mon'].'-'.$this->time['mday'].' '.$this->time['hours'].'-'.$this->time['minutes'].'-'.$this->time['seconds'].'\'');
	        list($this->offset, $rule_ident) = $result->fetch_row();
	        $result->free();
	
	        // dst rule is defined - go for it
	        if ($rule_ident !== null)
	        {
	            $rules = array();
	            // select all rules for current month and year
	            $sql_string  = 'SELECT
	                              dst_offset,
	                              startday,
	                              weekday,
	                              time,
	                              utc_correction,
	                              IF (endless = 1, '.$this->time['year'].', year) AS year_absolute
	                            FROM
	                              sys_timezone_rules
	                            WHERE
	                              rule_ident = \''.$this->unibox->db->cleanup($rule_ident).'\'
	                              AND
	                              utc_offset = '.$this->offset.'
	                              AND
	                              (
	                              year = '.$this->time['year'].'
	                              OR
	                              endless = 1
	                              )
	                              AND
	                              month = '.$this->time['mon'].'
	                            ORDER BY
	                              year_absolute';
	            if (!($result = $this->unibox->db->query($sql_string, 'failed to get rules for current month and year')))
	            	throw new ub_exception_runtime('failed to query timezone rule definition (by year and month) for date \''.$this->time['year'].'-'.$this->time['mon'].'-'.$this->time['mday'].' '.$this->time['hours'].'-'.$this->time['minutes'].'-'.$this->time['seconds'].'\'');
	            while (list($dst_offset, $startday, $weekday, $time, $utc_correction) = $result->fetch_row())
	            {
	                // recalculate startday for current year
	                $startday = $this->recalculate_startday($startday, $weekday);
	
	                // merge offsets
	                $total_offset = $this->offset + $dst_offset;
	
	                // split time
	                list($hours, $minutes) = explode(':', $time);
	                // switch which way to calculate
	                $this->time_calculate($startday, $this->time['mon'], $this->time['year'], $hours, $minutes, $total_offset, $to_utc);
	                if ($this->validate_rule())
	                {
	                    $rules['month_sort'][] = $this->time['mon'];
	                    $rules['day'][] = $startday;
	                    $rules['hours'][] = $hours;
	                    $rules['minutes'][] = $minutes;
	                    $rules['month'][] = $this->time['mon'];
	                    $rules['offset'][] = $dst_offset;
	                }
	            }
	            $result->free();
	
	            // last before beginning of current month
	            $sql_string  = 'SELECT
	                              dst_offset,
	                              startday,
	                              weekday,
	                              time,
	                              utc_correction,
	                              month,
	                              IF (endless = 1, '.$this->time['year'].', year) AS year_absolute,
	                              IF (month > '.$this->time['mon'].', month - 12, month) AS month_absolute
	                            FROM
	                              sys_timezone_rules
	                            WHERE
	                              rule_ident = \''.$this->unibox->db->cleanup($rule_ident).'\'
	                              AND
	                              utc_offset = '.$this->offset.'
	                              AND
	                              (
	                              (
	                              year = '.$this->time['year'].'
	                              AND
	                              month < '.$this->time['mon'].'
	                              )
	                              OR
	                              year < '.$this->time['year'].'
	                              )
	                            ORDER BY
	                              year_absolute DESC,
	                              month_absolute DESC';
	            if (!($result = $this->unibox->db->query($sql_string, 'failed to get last defined rule')))
		            throw new ub_exception_runtime('failed to query timezone rule definition (last month based) for date \''.$this->time['year'].'-'.$this->time['mon'].'-'.$this->time['mday'].' '.$this->time['hours'].'-'.$this->time['minutes'].'-'.$this->time['seconds'].'\'');
	            while (list($dst_offset, $startday, $weekday, $time, $utc_correction, $month, $year, $month_absolute) = $result->fetch_row())
	            {
	                // recalculate startday for current year
	                $startday = $this->recalculate_startday($startday, $weekday);
	                // merge offsets
	                $total_offset = $this->offset + $dst_offset;
	                // split time
	                list($hours, $minutes) = explode(':', $time);
	                // year to calculate rule for
	                $year = $this->time['year'];
	                if ($month_absolute <= 0)
	                    $year = $this->time['year'] - 1;
	                // switch which way to calculate
	                $this->time_calculate($startday, $month, $year, $hours, $minutes, $total_offset, $to_utc);
	                if ($this->validate_rule())
	                {
	                    $rules['month_sort'][] = $month_absolute;
	                    $rules['day'][] = $startday;
	                    $rules['hours'][] = $hours;
	                    $rules['minutes'][] = $minutes;
	                    $rules['month'][] = $month;
	                    $rules['offset'][] = $dst_offset;
	                    break;
	                }
	            }
	            $result->free();
	
	            // sort rules and get latest
	            if (count($rules) > 0)
	            {
	                array_multisort($rules['month_sort'], SORT_DESC, SORT_NUMERIC, $rules['day'], SORT_DESC, SORT_NUMERIC, $rules['hours'], SORT_DESC, SORT_NUMERIC, $rules['minutes'], SORT_DESC, SORT_NUMERIC);
	                $this->offset = $this->offset + $rules['offset'][0];
	            }
	        }
	
	        if ($to_utc && $this->type_from == TIME_TYPE_SERVER && $this->unibox->config->system->server_timezone_custom_offset != 0)
	            $this->offset = $this->offset + $this->unibox->config->system->server_timezone_custom_offset;
	
	        $this->time_calculate($this->time['mday'], $this->time['mon'], $this->time['year'], $this->time['hours'], $this->time['minutes'], $this->offset, $to_utc);
		}

		// save time
        $this->time_temp['seconds'] = $this->time['seconds'];

		$this->time = $this->time_temp;
        return true;
    }

    protected function time_calculate($day, $month, $year, $hours, $minutes, $offset, $to_utc)
    {
        if ($offset > 0)
            if ($to_utc)
                $this->time_substract($day, $month, $year, $hours, $minutes, $offset);
            else
                $this->time_add($day, $month, $year, $hours, $minutes, $offset);
        elseif ($offset < 0)
            if ($to_utc)
                $this->time_add($day, $month, $year, $hours, $minutes, $offset);
            else
                $this->time_substract($day, $month, $year, $hours, $minutes, $offset);
        else
        {
            $this->time_temp['mday'] = (int)$day;
            $this->time_temp['mon'] = (int)$month;
            $this->time_temp['year'] = (int)$year;
            $this->time_temp['hours'] = (int)$hours;
            $this->time_temp['minutes'] = (int)$minutes;
            $this->time_temp['seconds'] = 0;
        }
    }

    protected function time_substract($day, $month, $year, $hours, $minutes, $offset)
    {
        // make a copy of the time
        $this->time_temp['mday'] = (int)$day;
        $this->time_temp['mon'] = (int)$month;
        $this->time_temp['year'] = (int)$year;
        $this->time_temp['hours'] = (int)$hours;
        $this->time_temp['minutes'] = (int)$minutes;
        $this->time_temp['seconds'] = 0;

        $offset_minutes = floor($offset % 60);
        $offset_hours = floor($offset / 60);

        // shift minutes
        if (($this->time_temp['minutes'] = $this->time_temp['minutes'] - $offset_minutes) < 0)
        {
            $this->time_temp['minutes'] = 60 + $this->time_temp['minutes'];
            $this->time_temp['hours']--;
        }

        // shift hours
        if (($this->time_temp['hours'] = $this->time_temp['hours'] - $offset_hours) < 0)
        {
            $to = abs(floor($this->time_temp['hours'] / 24));
            for ($i = 1; $i <= $to; $i++)
            {
                $this->time_temp['hours'] += 24;
                $this->time_temp['mday']--;
            }
        }

        // get days per month
        $days_per_month = ub_functions::get_days_per_month($this->time_temp['year']);

        // shift days
        while ($this->time_temp['mday'] < 1)
        {
            $this->time_temp['mon']--;
            // shift month
            if ($this->time_temp['mon'] < 1)
            {
                $this->time_temp['mon'] = 12;
                $this->time_temp['year']--;
                $days_per_month = ub_functions::get_days_per_month($this->time_temp['year']);
            }
            $this->time_temp['mday'] = $this->time_temp['mday'] + $days_per_month[$this->time_temp['mon'] - 1];
        }

        // REVOLUTION!!!
        // days between 1582-10-04 and 1582-10-15 don't exist ;)
        // don't care for now
    }

    protected function time_add($day, $month, $year, $hours, $minutes, $offset)
    {
        // make a copy of the time
        $this->time_temp['mday'] = (int)$day;
        $this->time_temp['mon'] = (int)$month;
        $this->time_temp['year'] = (int)$year;
        $this->time_temp['hours'] = (int)$hours;
        $this->time_temp['minutes'] = (int)$minutes;
        $this->time_temp['seconds'] = 0;

        $offset_minutes = $offset % 60;
        $offset_hours = floor($offset / 60);

        // shift minutes
        if (($this->time_temp['minutes'] = $this->time_temp['minutes'] + $offset_minutes) >= 60)
        {
            $to = floor($this->time_temp['minutes'] / 60);
            for ($i = 1; $i <= $to; $i++)
            {
                $this->time_temp['minutes'] -= 60;
                $this->time_temp['hours']++;
            }
        }

        // shift hours
        if (($this->time_temp['hours'] = $this->time_temp['hours'] + $offset_hours) >= 24)
        {
            $to = floor($this->time_temp['hours'] / 24);
            for ($i = 1; $i <= $to; $i++)
            {
                $this->time_temp['hours'] -= 24;
                $this->time_temp['mday']++;
            }
        }

        // get days per month
        $days_per_month = ub_functions::get_days_per_month($this->time_temp['year']);

        // shift days
        while ($this->time_temp['mday'] > $days_per_month[$this->time_temp['mon'] - 1])
        {
            $this->time_temp['mday'] -= $days_per_month[$this->time_temp['mon'] - 1];
            $this->time_temp['mon']++;
            
            // shift month
            if ($this->time_temp['mon'] > 12)
            {
                $this->time_temp['mon'] = 1;
                $this->time_temp['year']++;
                $days_per_month = ub_functions::get_days_per_month($this->time_temp['year']);
            }
        }

        // REVOLUTION!!!
        // days between 1582-10-04 and 1582-10-15 don't exist ;)
        // don't care for now
    }

    protected function validate_rule()
    {
        // check if current time is later then dst rule begin time
        return  (   $this->time['year'] > $this->time_temp['year']
                    ||
                    (
                        (
                        $this->time['year'] == $this->time_temp['year']
                        &&
                        $this->time['mon'] > $this->time_temp['mon']
                        )
                        ||
                        (
                            (
                            $this->time['year'] == $this->time_temp['year']
                            &&
                            $this->time['mon'] == $this->time_temp['mon']
                            &&
                            $this->time['mday'] > $this->time_temp['mday']
                            )
                            ||
                            (
                                (
                                $this->time['year'] == $this->time_temp['year']
	                            &&
	                            $this->time['mon'] == $this->time_temp['mon']
	                            &&
                                $this->time['mday'] == $this->time_temp['mday']
                                &&
                                $this->time['hours'] > $this->time_temp['hours']
                                )
                                ||
                                (
                                    (
                                    $this->time['year'] == $this->time_temp['year']
		                            &&
		                            $this->time['mon'] == $this->time_temp['mon']
		                            &&
	                                $this->time['mday'] == $this->time_temp['mday']
	                                &&
                                    $this->time['hours'] == $this->time_temp['hours']
                                    &&
                                    $this->time['minutes'] >= $this->time_temp['minutes']
                                    )
                                )
                            )
                        )
                    )
                );
    }

    /*
    * info: 'sun' => 0
    *       'mon' => 1
    *       'tue' => 2
    *       'wed' => 3
    *       'thu' => 4
    *       'fri' => 5
    *       'sat' => 6
    */
    protected function recalculate_startday($startday, $weekday, $month = null, $year = null)
    {
        if ($month === null)
            $month = $this->time['mon'];
        if ($year === null)
            $year = $this->time['year'];
        
        // days per month
        $days_per_month = ub_functions::get_days_per_month($year);

        // last/next weekday x after/before the y of the month
        if ($startday != -1)
        {
            $weekday_of_startday = ub_functions::calculate_week_day(abs($startday), $this->time['mon'], $this->time['year']);
            if ($startday == abs($startday))
            {
                if ($weekday_of_startday <= $weekday)
                    $startday = $startday + ($weekday - $weekday_of_startday);
                else
                    $startday = $startday + (6 - $weekday_of_startday) + ($weekday + 1);
            }
            else
            {
                if ($weekday_of_startday >= $weekday)
                    $startday = abs($startday) - abs($weekday_of_startday - $weekday);
                else
                    $startday = abs($startday) - ((6 - $weekday) + ($weekday_of_startday + 1));
            }
        }
        // last weekday x of the month
        else
        {
            $last = ub_functions::calculate_week_day($days_per_month[$this->time['mon'] - 1], $this->time['mon'], $this->time['year']);
            if ($last >= $weekday)
                // substract the given weekday from the last weekday and all from the count of days
                $startday = $days_per_month[$this->time['mon'] - 1] - ($last - $weekday);
            else
                $startday = $days_per_month[$this->time['mon'] - 1] - ($last + 1 + (6 - $weekday));
        }
        return $startday;
    }
    
    public function compare_date($date_one, $date_two)
    {
        $time = new ub_time(TIME_TYPE_SERVER, TIME_TYPE_SERVER, false);

        if (!is_array($date_one))
        {
            $time->reset();
            $time->parse_date($date_one);
            $date_one = $time->get_array();
        }
        if (!is_array($date_two))
        {
            $time->reset();
            $time->parse_date($date_two);
            $date_two = $time->get_array();
        }

        // compare year
        if ($date_one['year'] > $date_two['year'])
            return -1;
        elseif ($date_one['year'] < $date_two['year'])
            return 1;

        // compare month
        if ($date_one['mon'] > $date_two['mon'])
            return -1;
        elseif ($date_one['mon'] < $date_two['mon'])
            return 1;

        // compare day
        if ($date_one['mday'] > $date_two['mday'])
            return -1;
        elseif ($date_one['mday'] < $date_two['mday'])
            return 1;

        // dates are identical
        return 0;
    }
    
    public function compare_time($time_one, $time_two)
    {
        $time = new ub_time(TIME_TYPE_SERVER, TIME_TYPE_SERVER, false);
        if (!is_array($time_one))
        {
            $time->reset();
            $time->parse_time($time_one);
            $time_one = $time->get_array();
        }
        if (!is_array($time_two))
        {
            $time->reset();
            $time->parse_time($time_two);
            $time_two = $time->get_array();
        }

        // compare hours
        if ($time_one['hours'] > $time_two['hours'])
            return -1;
        elseif ($time_one['hours'] < $time_two['hours'])
            return 1;

        // compare minutes
        if ($time_one['minutes'] > $time_two['minutes'])
            return -1;
        elseif ($time_one['minutes'] < $time_two['minutes'])
            return 1;

        // compare seconds
        if ($time_one['seconds'] > $time_two['seconds'])
            return -1;
        elseif ($time_one['seconds'] < $time_two['seconds'])
            return 1;

        // dates are identical
        return 0;
    }

    public function compare_datetime($datetime_one, $datetime_two)
    {
        $time = new ub_time(TIME_TYPE_SERVER, TIME_TYPE_SERVER, false);
        if (!is_array($datetime_one))
        {
            $time->reset();
            $time->parse_datetime($datetime_one);
            $datetime_one = $time->get_array();
        }
        if (!is_array($datetime_two))
        {
            $time->reset();
            $time->parse_datetime($datetime_two);
            $datetime_two = $time->get_array();
        }

        if (($result = $this->compare_date($datetime_one, $datetime_two)) != 0)
            return $result;
        return $this->compare_time($datetime_one, $datetime_two);
    }
}

?>