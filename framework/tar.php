<?php

class ub_tar
{
    protected $content = '';
    protected $files = array();
    protected $directories = array();

    /**
    * add_file()
    *
    * adds a file to the archive
    * subdirectories must be added first
    * 
    * @access   public
    * @param    string      file content
    * @param    string      file name
    * @param    integer     last modification date as timestamp (optional)
    */
    public function add($data, $name, $date = null)   
    {
        // Add file to processed data
        $current_file = array();
        $current_file['name'] = $name;
        $current_file['size'] = strlen($data);
        $current_file['time'] = ($date !== null) ? $date : time();
        $current_file['file'] = $data;

        // push file
        $this->files[] = $current_file;

        return true;
    }

	public function delete($name)
	{
		if (count($this->files) > 0)
			foreach ($this->files as $key => $file)
				if (stristr($file['name'], $name))
					unset($this->files[$key]);
	}

	public function dir($name, $date = null)
	{
		$current_dir = array();
        $current_dir['name'] = $name;
        $current_dir['size'] = 0;
        $current_dir['time'] = ($date !== null) ? $date : time();

		// push directory
		$this->directories[] = $current_dir;
	}

    // Generates a TAR file from the processed data
    public function output()
    {
        // clear data
        $this->content = '';

		// loop through directories
        if (count($this->directories) > 0)
        {
			foreach($this->directories as $key => $information)
			{
            	// build header
                $header = $this->build_header($information, 5);
	
				// Add new tar formatted data to tar file contents
				$this->content .= $header;
			}
        }

        // loop through files
        if (count($this->files) > 0)
        {
            foreach ($this->files as $key => $information)
            {
            	// build header
                $header = $this->build_header($information);

                // pad file contents to byte count divisible by 512
                $file_contents = str_pad($information['file'], (ceil($information['size'] / 512) * 512), chr(0));

                // add new tar formatted data to tar file contents
                $this->content .= $header.$file_contents;
            }
        }

        // add 512 bytes of NULLs to designate EOF
        $this->content .= str_repeat(chr(0), 512);

        return $this->content;
    }

	protected function build_header($information, $type = 0)
	{
		$header = '';

        // generate header
        // filename
        $header .= str_pad($information['name'], 100, chr(0));
        // permissions
        $header .= str_pad(decoct(0), 7, '0', STR_PAD_LEFT).chr(0);
        // UID
        $header .= str_pad(decoct(0), 7, '0', STR_PAD_LEFT).chr(0);
        // GID
        $header .= str_pad(decoct(0), 7, '0', STR_PAD_LEFT).chr(0);
        // size
        $header .= str_pad(decoct($information['size']), 11, '0', STR_PAD_LEFT).chr(0);
        // time
        $header .= str_pad(decoct($information['time']), 11, '0', STR_PAD_LEFT).chr(0);
        // checksum
        $header .= str_repeat(' ', 8);
        // typeflag
        $header .= (string)$type;
        // linkname
        $header .= str_repeat(chr(0), 100);
        // magic
        $header .= str_pad('ustar', 6, chr(32));
        // version
        $header .= chr(32).chr(0);
        // user name
        $header .= str_pad('', 32, chr(0));
        // group name
        $header .= str_pad('', 32, chr(0));
        // devmajor
        $header .= str_repeat(chr(0), 8);
        // devminor
        $header .= str_repeat(chr(0), 8);
        // prefix
        $header .= str_repeat(chr(0), 155);
        // end
        $header .= str_repeat(chr(0), 12);

        // compute header checksum
        $checksum = str_pad(decoct($this->compute_unsigned_checksum($header)), 6, '0', STR_PAD_LEFT);
        for($i = 0; $i < 6; $i++)
            $header[(148 + $i)] = substr($checksum, $i, 1);
        $header[154] = chr(0);
        $header[155] = chr(32);
        
        return $header;
	}

    // Computes the unsigned Checksum of a file's header
    // to try to ensure valid file
    protected function compute_unsigned_checksum($bytestring)
    {
        $unsigned_checksum = '';
        for($i = 0; $i < 512; $i++)
            $unsigned_checksum += ord($bytestring[$i]);
        for($i = 0; $i < 8; $i++)
            $unsigned_checksum -= ord($bytestring[148 + $i]);
        $unsigned_checksum += ord(' ') * 8;

        return $unsigned_checksum;
    }

}

?>