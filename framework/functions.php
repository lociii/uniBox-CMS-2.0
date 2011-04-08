<?php

/**
*
* uniBox 2.0 (winterspring)\n
* (c) Media Soma GbR
*
* 0.1       06.06.2005  pr      1st release\n
* 0.11      14.07.2005  jn      added comments & fixed missing php closing tag\n
* 0.12      28.03.2006  jn      added key_implode/key_explode
*
*/

class ub_functions
{
    /**
    * class version
    * 
    */
    const version = '0.1.0';

    /**
    * returns class version
    * 
    * @return       version-number (float)
    */
    public static function get_version()
    {
        return ub_administration::version;
    } // end get_version()

    /**
    * determines if an array is empty or has only empty values
    * 
    * @param        $arr                array to test (array)
    */
	public static function array_empty($arr)
	{
		if (!count($arr))
			return true;
		
        $return = true;
		foreach ($arr as $value)
		{
            if (is_array($value))
                $return = self::array_empty($value) && $return;
            elseif (trim($value) != '')
				return false;
		}
		
		return $return;
	}

    public static function array_intersect_key($isec)
    {
        $argc = func_num_args();
        for ($i = 1; !empty($isec) && $i < $argc; $i++)
        {
            $arr = func_get_arg($i);
            foreach ($isec as $k =>& $v)
                if (!isset($arr[$k]))
                    unset($isec[$k]);
        }
        return $isec;
    }

    public static function array_unique_recursive($array)
    {
        $new_array = array();
        if (is_array($array))
        {
            foreach($array as $key => $val)
            {
                if (is_array($val))
                    $val2 = self::array_unique_recursive($val);
                else
                {
                    $val2 = $val;
                    $new_array = array_unique($array);
                    break;
                }
                if (!empty($val2))
                    $new_array[$key] = $val2;
            }
        }
        return $new_array;
    }

    public static function key_implode($glue, $array)
    {
        $string = '';
        foreach ($array as $key => $value)
            $string .= $key.$glue.$value.$glue;
        return substr($string, 0, -(strlen($glue)));
    }

    public static function key_explode($separator, $string, $db_cleanup = false)
    {
        if ($db_cleanup)
            $unibox = ub_unibox::get_instance();

        $array = explode($separator, $string);
        $array_new = array();
        for ($i = 0; $i < count($array); $i+=2)
        {
            $array_new[$array[$i]] = (isset($array[$i + 1])) ? $array[$i + 1] : '';
            if ($db_cleanup)
                $array_new[$array[$i]] = $unibox->db->cleanup($array_new[$array[$i]]);
        }
        return $array_new;
    }

    /**
    * unescapes a string
    * 
    * @param        $str                string to unescape (string)
    */
	public static function unescape($str)
	{
		if (is_array($str))
		{
			foreach ($str as $key => $value)
				$str[$key] = self::unescape($value);
			return $str;
		}
		else
		{
			$patterns = 	array('/\\\n/ms',
								  '/\\\r/ms',
								  '/\\\t/ms');
								  
			$replacements = array("\n",
								  "\r",
								  "\t");
	
			return stripslashes(preg_replace($patterns, $replacements, $str));
		}
	}

    /**
    * convert 'carriage return / line feed' to 'line feed' only
    * 
    * @param        $str                string to strip cr from (string)
    */
	public static function strip_cr($str)
	{
		return preg_replace("/\r\n/ms", "\n", $str);
	}

	/**
	 * return the file extension for a given filename
	 * 
	 * @param		$filename			filename (string)
	 * @return 		file extension (string)
	 */
	public static function get_file_extension($filename)
	{
        if ($filename = strrchr($filename, '.'))
            if ($filename = substr($filename, 1, strlen($filename) - 1))
                return strtolower($filename);
		return '';
	}

    /**
     * return the filename without extension for a given filename
     * 
     * @param       $filename           filename (string)
     * @return      file name without extension (string)
     */
    public static function get_file_name($filename)
    {
        if ($substring = strrchr($filename, '/'))
            return strtolower(substr($substring, 1, strrpos($substring, '.') - 1));
        elseif ($substring = strrchr($filename, '\\'))
            return strtolower(substr($substring, 1, strrpos($substring, '.') - 1));
        elseif ($pos = strrpos($filename, '.'))
            return strtolower(substr($filename, 0, $pos));
        else
            return '';
    }

	/**
	 * converts filesize given in php-ini short-hand notation to bytes
	 * 
	 * @param 		$val		filesize in short-hand notation (string)
	 * @return		filesize in bytes (int)
	 */
	public static function sh2bt($val)
	{
	    $val = trim($val);
	    $last = strtolower($val{strlen($val)-1});
	    switch ($last)
    	{
	        case 'k':
	            return (int) $val * 1024;
	        case 'm':
	            return (int) $val * 1048576;
	        default:
	            return $val;
    	}
	} // end sh2bt()

	/**
	 * converts fizesize given in bytes to a human-readable format
	 * 
	 * @param		$val			filesize in bytes (int)
     * @param       $decimals       number of digits after the decimal point (int)
	 * @return		human-readable filesize (string)
	 */
	public static function bt2hr($val, $decimals = 1)
 	{
 		if ($val < 1024)
	 		return array('size' => $val, 'unit' => 'TRL_ABBR_BYTES');
		elseif ($val < 1048576)
			return array('size' => round($val / 1024, $decimals), 'unit' => 'TRL_ABBR_KB');
		elseif ($val < 1073741824)
			return array('size' => round($val / 1048576, $decimals), 'unit' => 'TRL_ABBR_MB');
		else
			return array('size' => round($val / 1073741824, $decimals), 'unit' => 'TRL_ABBR_GB');
 	} // end bt2hr()

	/*
	 * returns a random string
	 * 
	 * @param		$length			length of random string (integer)
	 * @param		$use_uppercase	should uppercase letters be included in the string (bool)
	 * @return		random string
	 */
	public static function get_random_string($length, $use_uppercase = false)
	{
	    $charset = 'abcdefghijklmnopqrstuvwxyz';
	    if ($use_uppercase)
	    	$charset .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	    $charset .= '1234567890';
	    $max_index = strlen($charset) - 1;
	
		// seed random number generator & shuffle charset
		$charset = str_shuffle($charset);
	
	    $rand_str = '';
	    for ($i = 0; $i < $length; $i++)
	    {
		    mt_srand(crc32(mt_rand()));
	    	$rand_str .= $charset[mt_rand(0, $max_index)];
	    }
	    
	    return $rand_str;
	}

    public static function encode_ip($ip = null)
    {
        if ($ip === null)
            $ip = self::get_ip();
        $ip_parts = explode('.', $ip);
        return sprintf('%02x%02x%02x%02x', $ip_parts[0], $ip_parts[1], $ip_parts[2], $ip_parts[3]);
    }

    public static function decode_ip($ip = null)
    {
        if ($ip === null)
            $ip = self::get_ip();
        $ip_parts = explode('.', chunk_split($ip, 2, '.'));
        return hexdec($ip_parts[0]). '.' . hexdec($ip_parts[1]) . '.' . hexdec($ip_parts[2]) . '.' . hexdec($ip_parts[3]);
    }

    public function get_ip()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        else
            return $_SERVER['REMOTE_ADDR'];
    }

    /**
    * return bit components of given bitmask
    * 
    * @param        $num                bitmask (int)
    * @return       bit components (array)
    */
    public static function get_bit_components($num)
    {
        $return = array();
        $str = strrev((string) decbin($num));
        for ($i = 0; $i < strlen($str); $i++)
            if ((bool)$str[$i])
                $return[] = pow(2, $i);
        return $return;
    }

    public static function image_text_wrapped(&$img, $x, $y, $width, $font, $color, $text, $text_size, $align = 'l')
    {
        // Recalculate X and Y to have the proper top/left coordinates instead of TTF base-point
        $y += $text_size;
        // use a custom string to get a fixed height.
        $dimensions = imagettfbbox($text_size, 0, $font, 'MXQJPmxqjp123');
        $x -= $dimensions[4] - $dimensions[0];

        // Remove windows line-breaks
        $text = str_replace("\r", '', $text); 
        // Split text into "lines"
        $srcLines = split("\n", $text);
        // The destination lines array.
        $dstLines = array();

        foreach ($srcLines as $currentL)
        {
            $line = '';
            //Split line into words.
            $words = split (" ", $currentL);
            foreach ($words as $word)
            {
                $dimensions = imagettfbbox($text_size, 0, $font, $line.$word);
                // get the length of this line, if the word is to be included
                $lineWidth = $dimensions[4] - $dimensions[0];
                // check if it is too big if the word was added, if so, then move on.
                if ($lineWidth > $width && !empty($line) )
                {
                    //Add the line like it was without spaces.
                    $dstLines[] = ' '.trim($line);
                    $line = '';
                }
                $line .= $word.' ';
            }
            $dstLines[] =  ' '.trim($line); //Add the line when the line ends.
        }

        // Calculate lineheight by common characters.
        //use a custom string to get a fixed height.
        $dimensions = imagettfbbox($text_size, 0, $font, 'MXQJPmxqjp123');
        // get the heightof this line
        $lineHeight = $dimensions[1] - $dimensions[5];

        // Takes the first letter and converts to lower string. Support for Left, left and l etc.
        $align = strtolower(substr($align,0,1));
        foreach ($dstLines as $nr => $line)
        {
            if ($align != "l")
            {
                $dimensions = imagettfbbox($text_size, 0, $font, $line);
                // get the length of this line
                $lineWidth = $dimensions[4] - $dimensions[0];
                //If the align is Right
                if ($align == "r")
                    $locX = $x + $width - $lineWidth;
                //If the align is Center
                else
                    $locX = $x + ($width/2) - ($lineWidth/2);
            }
            //if the align is Left
            else
                $locX = $x;
            $locY = $y + ($nr * $lineHeight);
            // Print the line.
            imagettftext($img, $text_size, 0, $locX, $locY, $color, $font, $line);
        }       
    }

    public static function validate_url($url, $allowed_uri_schemes)
    {
    	$matches = array();
        // validate url structure
        //
        //               start
        //                 scheme
        //                                     only absolute urls
        //                                        username and password (optional)
        //                                                      domain
        //                                                                                  port (optional)
        //                                                                                            end
        if (!preg_match('~^([a-z][a-z0-9+.\-]*)://(\w+(:\w+)?@)?[a-z0-9äüö]([a-z0-9äöü.\-])*(:[0-9]+)?~ix', $url, $matches))
            return false;

        if (!in_array(strtolower($matches[1]), $allowed_uri_schemes))
            return false;

        return true;
    }

    public static function tar_check_extract_content($tar, $path, $path_separator = '/')
    {
    	if (substr($path, -1, 1) != $path_separator)
    		$path .= $path_separator;

        $return = true;
        foreach ($tar as $name => $entry)
        {
            if ($entry instanceof StdClass)
                $return = self::tar_check_extract_content($entry, $path.$name, $path_separator) && $return;
            elseif ((file_exists($path.$name) && !is_writable($path.$name)) || (!file_exists($path.$name) && file_exists($path) && !is_writable($path)))
                return false;
        }
        return $return;
    }

    public static function tar_extract_content($tar, $path, $path_separator = '/')
    {
    	if (substr($path, -1, 1) != $path_separator)
    		$path .= $path_separator;

		$return = true;
        foreach ($tar as $name => $entry)
        {
            if ($entry instanceof StdClass)
            {
                if (!file_exists($path.$name) && !@mkdir($path.$name))
                    return false;
                $return = self::tar_extract_content($entry, $path.$name, $path_separator) && $return;
            }
            else
                $return = (bool)file_put_contents($path.$name, $entry) && $return;
        }
        return $return;
    }

    public static function tar_get_content($file, $mime_file_type = 'application/x-tar')
    {
    	// set default file handling
        $open_function = 'fopen';
        $read_function = 'fread';
        $close_function = 'fclose';
    	
        if ($mime_file_type == 'application/x-bzip2')
        {
            $open_function = 'bzopen';
            $read_function = 'bzread';
            $close_function = 'bzclose';
        }
        elseif ($mime_file_type == 'application/x-gzip')
            $open_function = 'gzopen';

        if ($fp = @$open_function($file, 'r'))
        {
            $tar_content = new StdClass();
            while ($block = $read_function($fp, 512))
            {
                $current = &$tar_content;
                
                // get tar file information
                $temp = unpack('a100name/a8mode/a8uid/a8gid/a12size/a12mtime/a8checksum/a1type/a100symlink/a6magic/a2temp/a32temp/a32temp/a8temp/a8temp/a155prefix/a12temp', $block);
                $file = array(
                              'name' => $temp['prefix'] . $temp['name'],
                              'stat' => array(2 => $temp['mode'],
                                              4 => octdec($temp['uid']),
                                              5 => octdec($temp['gid']),
                                              7 => octdec($temp['size']),
                                              9 => octdec($temp['mtime'])),
                              'checksum' => octdec($temp['checksum']),
                              'type' => $temp['type'],
                              'magic' => $temp['magic']);

                if ($file['checksum'] == 0x00000000)
                    break;
                elseif (substr($file['magic'], 0, 5) != 'ustar')
                    return false;

                $block = substr_replace($block, '        ', 148, 8);
                $checksum = 0;
                for ($i = 0; $i < 512; $i++)
                    $checksum += ord(substr($block, $i, 1));

                if ($file['checksum'] != $checksum)
                    return false;
                    
                // read data
                // read only 8192 bytes to workaround a bzread bug
                $length = $file['stat'][7];
                $file['data'] = '';
                while ($length > 8192)
                {
                    $file['data'] .= $read_function($fp, 8192);
                    $length -= 8192;
                }
                if ($length > 0)
                    $file['data'] .= $read_function($fp, $length);

				if ($file['stat'][7] % 512 > 0)
                	$read_function($fp, 512 - $file['stat'][7] % 512);

                $name = explode('/', $file['name']);
                $filename = end($name);
                unset($name[key($name)]);
                if (count($name) > 0)
                {
                    reset($name);
                    foreach ($name as $dir)
                    {
                        if (!isset($current->$dir))
                            $current->$dir = new StdClass();
                        $current = &$current->$dir;
                    }
                }
                // add content
                $current->$filename = $file['data'];
                unset ($file);
            }
            $close_function($fp);
        }
        return $tar_content;
    }

    public static function year_is_leap($year)
    {
        // leap years until 1599: all years,    divisible by 4
        // leap years since 1600: all years,    divisible by 4
        //                                      but not by 100 except if by 400
        $leap_year = false;
        if ($year < 1600 && $year % 4 == 0)
            $leap_year = true;
        elseif ($year >= 1600 && $year % 4 == 0 && $year % 100 != 0)
            $leap_year = true;
        elseif ($year >= 1600 && $year % 4 == 0 && $year % 100 == 0 && $year % 400 == 0)
            $leap_year = true;
        return $leap_year;
    }

    public static function calculate_week_day($day, $month, $year)
    {
        // 0001-01-01 was a saturday - trust me ;)
        // count days from then
        // the count of days module 7 is the weekday
        // 0-6 so-sa
    
        $days_per_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
    
        // get day count of all years + days in current month
        $days = ((($year - 1) * 365) + ($day - 1));
        // get count of days for the current year (but not for the current month)
        for ($i = 0; $i < ($month - 1); $i++)
            $days += $days_per_month[$i];
    
        // REVOLUTION!!!
        // days between 1582-10-04 and 1582-10-15 don't exist ;)
        if($year > 1582 || $year == 1582 && ($month > 10 || $month == 10 && $day > 4))
            $days -= 10;

        // count all leap year until now
        // leap years until 1599: all years,    divisible by 4
        // leap years since 1600: all years,    divisible by 4
        //                                      but not by 100 except if by 400
        $leapyears = floor($year / 4);
        if (bcmod($year, 4) == 0 && $month < 3)
            $leapyears--;
        if ($year >= 1600)
        {
            $leapyears -= floor(($year-1600) / 100);
            $leapyears += floor(($year-1600) / 400);
            if (bcmod($year, 100) == 0 && $month < 3)
            {
                $leapyears++;
                if (bcmod($year, 400) == 0)
                    $leapyears--;
            }
        }
        // add additional days of leap years
        $days += $leapyears;

        // until now 0 was saturday - correct it
        if ($days % 7 == 0)
            return 6;
        else
            return $days % 7 - 1;
    }

    public static function calculate_week_of_year($day, $month, $year)
    {
        $days_per_month = self::get_days_per_month($year);
        $days_passed = $day;

		if ($month > 1)
        	for ($i = 0; $i <= $month - 2; $i++)
            	$days_passed += $days_per_month[$i];

		// get first sunday of year
		$subtract = self::calculate_week_day(1, 1, $year);
		if ($subtract == 0)
			$subtract = 1;
		else
			$subtract = 8 - $subtract;
		$days_passed -= $subtract;

        return ceil($days_passed / 7);
    }

    public static function get_days_per_month($year)
    {
        $days_per_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
        if (self::year_is_leap($year))
            $days_per_month[1] = 29;
        return $days_per_month;
    }

	/*
	 * 
	 * -1 -> version 1 < version 2
	 * 0 -> both versions equal
	 * 1 -> version 1 > version 2
	 * 
	 */
	public static function compare_versions($version1, $version2)
	{
		// match version1
        $matches = array();
        preg_match('/((\d+)\.)+\d+/', $version1, $matches);
        $version1 = explode('.', $matches[0]);
        
   		// match version2
        $matches = array();
        preg_match('/((\d+)\.)+\d+/', $version2, $matches);
        $version2 = explode('.', $matches[0]);

		foreach ($version1 as $key => $value)
		{
			if (isset($version2[$key]) && $value < $version2[$key])
				return -1;
			elseif (!isset($version2[$key]) || $value > $version2[$key])
				return 1;
			unset($version2[$key]);
		}
		if (!empty($version2))
			return -1;
		return 0;
	}

}

?>