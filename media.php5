<?php

@set_time_limit(0);

/**
*
* uniBox 2.0 (winterspring)
*
* Media Soma - Gestaltung und interaktive Technologien
* Raphael Fischer, Oliver Kieffer und Jens Nistler GbR
* Rathausstrasse 75-79
* 66333 Voelklingen
*
* (c) Media Soma GbR     -    jens nistler (jens.nistler@media-soma.de)
*                             philipp von styp-rekowsky (philipp.styp-rekowsky@media-soma.de)
*
*/

	abstract class ub_exception extends Exception
	{
	    protected $message = null;
	    
		public function __construct($message = null)
		{
	        $this->message = $message;
		}
		
		public function get_message()
		{
			return $this->message;
		}
	}
	
	class ub_exception_runtime extends ub_exception
	{
	    public function __construct($message = null)
	    {
	        parent::__construct($message);
	    }
	}

	class ub_exception_database extends ub_exception
	{
	    public function __construct($message = null)
	    {
	        parent::__construct($message);
	    }
	}

	class ub_exception_transaction extends ub_exception
	{
	    public function __construct($message = null)
	    {
	        parent::__construct($message);
	    }
	}

	// try loading constants
	if (!is_readable('constants.php'))
	    die('the file containing the constants could not be found');
	include('constants.php');
	
	// define autoloader
	function __autoload($classname)
	{
		$framework_classes = array( 'ub_db' => array(
		                    			DIR_FRAMEWORK_DATABASE.'database.php',
		                    			DIR_FRAMEWORK_DATABASE.'common.php'),
		                			'ub_db_layer_mysql' => array(
		                    			DIR_FRAMEWORK_DATABASE_LAYERS.'mysql.php'),
		                			'ub_db_layer_mysqli' => array(
		                    			DIR_FRAMEWORK_DATABASE_LAYERS.'mysqli.php'),
	            					'ub_db_tools_mysql' => array(
	                					DIR_FRAMEWORK_DATABASE_TOOLS.'mysql.php'),
									'ub_functions' => array(
		                    			DIR_FRAMEWORK.'functions.php'));
	
	    if (array_key_exists($classname, $framework_classes))
	        foreach ($framework_classes[$classname] as $filename)
	        {
	            if (!is_readable($filename))
	                die('the file containing the class \''.$classname.'\' could not be found/opened');
	            include($filename);
	        }
	}

	class ub_media_delivery
	{
		public function __construct()
		{
			// check for requested media
			if (!isset($_GET['media_id']) || (string)(int)$_GET['media_id'] != $_GET['media_id'])
				die();

			// check for session id
			if (isset($_COOKIE[SESSION_NAME.'_id']))
			    $temp_session_id = $_COOKIE[SESSION_NAME.'_id'];
			elseif (isset($_POST[SESSION_NAME.'_id']))
			    $temp_session_id = $_POST[SESSION_NAME.'_id'];
			elseif (isset($_GET[SESSION_NAME.'_id']))
			    $temp_session_id = $_GET[SESSION_NAME.'_id'];
			else
				die();

			// check for resize
			$width = $height = null;
		    if (isset($_GET['width']) && (string)(int)$_GET['width'] == $_GET['width'])
		    	$width = (int)$_GET['width'];
		    if (isset($_GET['height']) && (string)(int)$_GET['height'] == $_GET['height'])
		    	$height = (int)$_GET['height'];

			// initialize variables
			$media = $categories = array();
			$this->db = ub_db::get_instance();

			try
			{
				// get allowed media categories
				$sql_string  = 'SELECT
								  rights_categories
								FROM
								  sys_sessions
								WHERE
								  session_id = \''.$this->db->cleanup($temp_session_id).'\'';
				$result = $this->db->query($sql_string);
				if ($result->num_rows() != 1)
					die();

				list($rights) = $result->fetch_row();
				$result->free();

				// unserialzie rights
				$rights = unserialize($rights);
				if (isset($rights['media_show']))
					$categories = $rights['media_show'];

				// get allowed single media
				$sql_string  = 'SELECT
								  media_id
								FROM
								  data_media_access
								WHERE
								  session_id = \''.$this->db->cleanup($temp_session_id).'\'';
				$result = $this->db->query($sql_string);
				while (list($media_id) = $result->fetch_row())
					$media[] = $media_id;
				$result->free();

				// check if the user is allowed to view any media
				if (count($categories) == 0 && count($media) == 0)
					die();

				// get media data
				$sql_string  = 'SELECT
								  a.media_id,
								  a.media_width,
								  a.media_height,
								  a.file_name,
								  a.media_link,
								  a.file_extension,
								  a.category_id,
								  b.mime_type,
				                  b.mime_subtype,
				                  b.mime_binary,
								  c.detail_value
								FROM
								  data_media_base AS a
									INNER JOIN sys_mime_types AS b
				                      ON b.mime_file_extension = a.mime_file_extension
									INNER JOIN sys_category_details AS c
									  ON
									  (
									  c.category_id = a.category_id
									  AND
									  c.detail_ident = \'cacheable\'
									  )
								WHERE
								  a.media_id = '.(int)$_GET['media_id'].'
								  AND
								  (';
				if (count($categories) > 0)
					$sql_string .= 'a.category_id IN ('.implode(', ', $categories).')';
				if (count($media) > 0)
				{
					if (count($categories) > 0)
						$sql_string .= ' OR ';
					$sql_string .= 'a.media_id IN ('.implode(', ', $media).')';
				}
				$sql_string .= ')';
				$result = $this->db->query($sql_string, 'failed to get data for id '.$media_id);
				if ($result->num_rows() == 1)
				{
				    list($media_id, $media_width, $media_height, $filename, $link, $extension, $category_id, $type, $subtype, $binary, $cacheable) = $result->fetch_row();
				    $result->free();

				    // update hit count
				    $sql_string  = 'UPDATE
				                      data_media_base
				                    SET
				                      media_hits = media_hits + 1
				                    WHERE
				                      media_id = '.$media_id;
				    $this->db->query($sql_string, 'failed to update hits');

					// redirect if external media
					if ($link !== null)
					{
						header('Location: '.$link);
		                die();
					}

					// 
					$file = DIR_MEDIA_BASE.$category_id.'/'.$media_id.'.'.$extension;

					// check if image needs to be resized
					if (in_array($subtype, array('gif', 'jpeg', 'png')) && (($width !== null && $width != $media_width) || ($height !== null && $height != $media_height)))
					{
						list($width, $height) = $this->calculate($media_width, $media_height, $width, $height);

						// cache resized image
						$cache_dir = DIR_MEDIA_CACHE.$category_id.'/'.$media_id.'/';
						$cache_file = $width.'_'.$height.'.'.$extension;
						if (!file_exists($cache_dir.'/'.$cache_file))
						{
							$image = $this->resize($file, $subtype, $media_width, $media_height, $width, $height);

							if (strlen($image) <= 512000)
							{
								// check if cache exists
								if (!file_exists(DIR_MEDIA_CACHE))
									mkdir(DIR_MEDIA_CACHE);
								// check if category cache exists
								if (!file_exists(DIR_MEDIA_CACHE.$category_id))
									mkdir(DIR_MEDIA_CACHE.$category_id);
								// check if single media cache exists
								if (!file_exists($cache_dir))
									mkdir($cache_dir);
	
								// save resized image
					            switch ($subtype)
					            {
					                case 'gif':
					                    imagegif($image, $cache_dir.'/'.$cache_file);
					                    break;
					                case 'jpeg':
					                    imagejpeg($image, $cache_dir.'/'.$cache_file);
					                    break;
					                case 'png':
					                    imagepng($image, $cache_dir.'/'.$cache_file);
					                    break;
					            }
							}
						}
						else
							$file = $cache_dir.'/'.$cache_file;
					}

					// send header and media
					if (isset($image))
					{
						$this->send_header($type, $subtype, strlen($image), $filename, $extension, $cacheable);
						switch ($subtype)
			            {
			                case 'gif':
			                    imagegif($image);
			                    break;
			                case 'jpeg':
			                    imagejpeg($image);
			                    break;
			                case 'png':
			                    imagepng($image);
			                    break;
			            }
					}
					else
					{
						$this->send_header($type, $subtype, filesize($file), $filename, $extension, $cacheable);
						readfile($file);
					}
				}
			}
			catch (ub_exception $exception)
			{
				die();
			}
		}

		protected function calculate($old_width, $old_height, $new_width, $new_height)
		{
			// max size = old size
			if ($new_width !== null && $new_width > $old_width)
				$new_width = $old_width;
			if ($new_height !== null && $new_height > $old_height)
				$new_height = $old_height;

			// calculate new size
			if (($new_width === null && $new_height !== null) || ($new_width !== null && $new_height !== null && $old_height > $old_width))
				$new_width = $old_width * ($new_height / $old_height);
	        elseif ($new_width !== null && $new_height === null || ($new_width !== null && $new_height !== null && $old_height < $old_width))
				$new_height = $old_height * ($new_width / $old_width);
	        return array(ceil($new_width), ceil($new_height));
		}

		protected function resize($file, $mime, $old_width, $old_height, $new_width, $new_height)
		{
			// load image
			switch ($mime)
            {
                case 'gif':
                    $media = imagecreatefromgif($file);
                    $color_transparent = imagecolortransparent($media);
                    break;
                case 'jpeg':
                    $media = imagecreatefromjpeg($file);
                    break;
                case 'png':
                    $media = imagecreatefrompng($file);
                    break;
            }

	        // create new image
	        $function_name = (function_exists('imagecreatetruecolor') && $mime != 'gif') ? 'imagecreatetruecolor' : 'imagecreate';
	        $media_resized = $function_name($new_width, $new_height);

	        // set transparency if any
	        if ($mime == 'gif' && $color_transparent)
	        {
	        	imagepalettecopy($media_resized, $media);
				imagefill($media_resized, 0, 0, $color_transparent);
				imagecolortransparent($media_resized, $color_transparent);
	        }

			// copy resized image
	        $function_name = (function_exists('imagecopyresampled') && $mime != 'gif') ? 'imagecopyresampled' : 'imagecopyresized';
    		$function_name($media_resized, $media, 0, 0, 0, 0, $new_width, $new_height, $old_width, $old_height);

			return $media_resized;
		}
		
		protected function send_header($type, $subtype, $size, $name, $extension, $cache = false)
		{
	    	$date = gmdate('D, d M Y H:i:s');
	
	        // make sure this thing doesn't cache
	        $disposition = ($type == 'image') ? 'inline' : 'attachment';
	        $content_type = (!empty($type) && !empty($subtype)) ? $type.'/'.$subtype : 'application/force-download';
	        header('Content-Type: '.$content_type);
	        header('Last-Modified: '.$date.' GMT');

			// process cache type
	        if (!$cache)
	        {
		        header('Cache-Control: no-store');
		        header('Cache-Control: max-age=0');
	        }
	        else
	        {
	        	header('Cache-Control: public');
	        	header('Cache-Control: max-age=86400');
	        }

            header('Content-Length: '.($size));
            header('Content-Disposition: '.$disposition.'; filename='.$name.'.'.$extension);
		}
	}

new ub_media_delivery;

?>