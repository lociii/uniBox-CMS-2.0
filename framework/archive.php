<?php

/**
* uniBox 2.0 (winterspring)
* (c) Media Soma GbR
* 
* factory class for different methods of ub_compress
*
* 0.1       06.10.2006  jn      1st release
* 
*/

class ub_archive
{
    protected $tar;

    public function __construct($compression_method = UB_COMPRESS_NONE, $compression_level = 9)
    {
        $this->tar = new ub_tar;
        switch ($compression_method)
        {
            case UB_COMPRESS_ZIP:
                if (!extension_loaded('zlib'))
                    throw new ub_exception_runtime('zlib extension not found');
                break;
            case UB_COMPRESS_BZ2:
                if (!extension_loaded('bz2'))
                    throw new ub_exception_runtime('bzip extension not found');
                break;
        }
        $this->compression_method = $compression_method;

        if (!is_numeric($compression_level))
            throw new ub_exception_runtime('compression level must be an integer between 0 and 9');

        if ($compression_level < 0)
            $this->compression_level = 0;
        elseif ($compression_level > 9)
            $this->compression_level = 9;
        else
            $this->compression_level = abs($compression_level);
    }

    public function output()
    {
        $content = $this->tar->output();
        
        switch ($this->compression_method)
        {
            case UB_COMPRESS_ZIP:
                $content = gzencode($content, $this->compression_level);
                break;
            case UB_COMPRESS_BZ2:
                $content = bzcompress($content, $this->compression_level);
                break;
        }
        return $content;
    }

    public function add($data, $name, $time = null)
    {
        $this->tar->add($data, $name, $time);
    }
    
    public function delete($name)
    {
    	$this->tar->delete($name);
    }

	public function dir($name)
	{
		$this->tar->dir($name, $date = null);
	}

}

?>