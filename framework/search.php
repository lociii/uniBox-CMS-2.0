<?php

#################################################################################################
###
### uniBox 2.0 (winterspring)
### (c) Media Soma GbR
###
#################################################################################################

class ub_search
{
    /**
    * $instance
    *
    * instance of own class
    * 
    * @access   protected
    */
    private static $instance = NULL;

    /**
    * $drop_chars
    *
    * characters to cut from text
    * 
    * @access   protected
    */
    private static $drop_chars   = array('!', '¡', '?', '¿', '"', '\'', '#', '$', '&', '(', ')', '*', '+', '-', ',', '.', '/', ':', ';', '<', '>', '=', '@', '[', ']', '\\', '^', '_', '`', '{', '}', '|', '~', '¢', '£', '¤', '¥', '¦', '§', '©', '«', '»', '®', '%');

    /**
    * $unibox
    *
    * framework object
    * 
    * @access   protected
    */
    protected $unibox;

    /**
    * get_instance()
    *
    * return class instance
    * 
    * @access   public
    * @return   object      ub_search object
    */
    public static function get_instance()
    {
        if (self::$instance === null)
            self::$instance = new ub_search;
        return self::$instance;
    } // end get_instance()

    /**
    * __construct()
    *
    * class constructor
    * 
    * @access   protected
    */
    protected function __construct()
    {
        $this->unibox = ub_unibox::get_instance();
    }

    /**
    * index()
    *
    * main indexing function - calls every other function
    * 
    * @access   public
    */
    public function index($text, $module_ident, $content_ident, $lang_ident = null, $quality = 1)
    {
        // remove old result
        $this->remove($module_ident, $content_ident, $lang_ident);
        // build word array
        $words = $this->get_word_array($text, $lang_ident);

        // get id's of already known words
        $matches = array();
        $sql_string  = 'SELECT
                          word_id,
                          word
                        FROM
                          data_search_words
                        WHERE
                          word IN (\''.implode('\', \'', array_keys($words)).'\')';
        $result = $this->unibox->db->query($sql_string, 'failed to get list of already indexed words');
        if ($result->num_rows() > 0)
            // delete found words from array and add them to the matchlist
            while (list($word_id, $word) = $result->fetch_row())
            {
                $matches[] = array('id' => $word_id, 'position' => $words[$word]['position']);
                unset($words[$word]);
            }

        // add new words and get their ids
        $added_words = $this->add_words($words);
        $matches = array_merge($matches, $added_words);

        foreach ($matches as $data)
        {
        	$sql_string  = 'INSERT INTO
							  data_search_wordmatch
							SET
							  module_ident = \''.$this->unibox->db->cleanup($module_ident).'\',
							  content_ident = \''.$this->unibox->db->cleanup($content_ident).'\',
							  lang_ident = '.($lang_ident === null ? '\'\'' : '\''.$this->unibox->db->cleanup($lang_ident).'\'').',
							  word_id = '.$data['id'].',
							  quality = '.(float)$quality.',
							  position = '.$data['position'];
			$this->unibox->db->query($sql_string, 'failed to index word');
        }
    }

    protected function remove($module_ident, $content_ident, $lang_ident = null)
    {
        $sql_string  = 'DELETE FROM
                          data_search_wordmatch
                        WHERE
                          module_ident = \''.$this->unibox->db->cleanup($module_ident).'\'
                          AND
                          content_ident = \''.$this->unibox->db->cleanup($content_ident).'\'
                          AND
                          lang_ident = '.($lang_ident === null ? '\'\'' : '\''.$this->unibox->db->cleanup($lang_ident).'\'');
        $this->unibox->db->query($sql_string, 'failed to delete from wordmatch table');
        
        $this->index_cleanup();
    }

    protected function get_word_array($text, $language)
    {
        $words_to_drop = array();

        // obtain bad words
        $sql_string  = 'SELECT
                          word
                        FROM
                          data_search_badwords';
        $result = $this->unibox->db->query($sql_string, 'failed to get badwords');
        while (list($word) = $result->fetch_row())
            $words_to_drop[] = $word;

        // replace line endings by a space
        $text = preg_replace('/[\n\r]/is', ' ', $text);

        // remove ubcode and urls
        $ubc = new ub_ubc();
        $text = $ubc->strip_ubc($text);

        // replace symbols and punctuations
        $words_to_drop = array_merge($words_to_drop, self::$drop_chars);
        $text = str_replace($words_to_drop, ' ', $text);

        // split words
        $text = explode(' ', $text);

		$result = array();

		$position = 0;
        foreach ($text as $key => $value)
        {
        	if (empty($value))
        		continue;

			$position++;
        	$length = strlen($value);

            // remove small, large and bad words
            if ($length >= $this->unibox->config->system->search_minimum_wordlength && $length <= $this->unibox->config->system->search_maximum_wordlength)
            {
            	$value = trim($value);

				$substrings = array();

				$length = strlen($value);
				for ($i = 3; $i <= $length; $i++)
					for ($j = 0; $j <= $length - $i; $j++)
					{
						$substring = $this->unibox->db->cleanup(substr($value, $j, $i));
						if (!in_array($substring, $substrings))
							$substrings[] = $substring;
					}

                $result[$this->unibox->db->cleanup($value)] = array('position' => $position, 'substrings' => $substrings);
            }
        }
        return $result;
    }

    protected function add_words($words)
    {
    	$return = array();
    	foreach ($words as $word => $data)
    	{
    		$length = strlen($word);

    		// insert word
    		$sql_string  = 'INSERT INTO
							  data_search_words
							SET
							  word = \''.$word.'\'';
			$this->unibox->db->query($sql_string, 'failed to insert word');
			$word_id = $this->unibox->db->last_insert_id();

    		// check for new substrings
    		$substrings = array();
    		$sql_string  = 'SELECT
							  substring_id,
							  substring
							FROM
							  data_search_substrings
							WHERE
							  substring IN (\''.implode('\', \'', $data['substrings']).'\')';
			$result = $this->unibox->db->query($sql_string, 'failed to check for already existing substrings');
			while (list($substring_id, $substring) = $result->fetch_row())
				$substrings[$substring] = $substring_id;

			// process new substrings
			$new_substrings = array_diff($data['substrings'], array_keys($substrings));

    		// insert new substrings
    		foreach ($new_substrings as $new_substring)
    		{
    			$sql_string  = 'INSERT INTO
								  data_search_substrings
								SET
								  substring = \''.$new_substring.'\'';
				$this->unibox->db->query($sql_string, 'failed to insert substring');
				$substrings[$new_substring] = $this->unibox->db->last_insert_id();
    		}

			// associate all substrings with the given word
			foreach ($data['substrings'] as $substring)
			{
				$sql_string  = 'INSERT INTO
								  data_search_word_substrings
								SET
								  word_id = '.$word_id.',
								  substring_id = '.$substrings[$substring].',
								  weight = '.round((strlen($substring) / $length), 2);
				$this->unibox->db->query($sql_string, 'failed to associate word with substring');
			}
			$return[] = array('id' => $word_id, 'position' => $words[$word]['position']);
    	}
    	return $return;
    }

    protected function tokenize_search($string)
    {
       for ($tokens = array(), $next_token = strtok($string, ' '); $next_token !== false; $next_token = strtok(' '))
       {
           if ($next_token{0} == '"' || ($next_token{0} == '-' && $next_token{1} == '"'))
               $next_token = $next_token{strlen($next_token)-1} == '"' ? substr($next_token, 1, -1) : substr($next_token, 1) . ' ' . strtok('"');
           $tokens[] = $next_token;
       }
       return $tokens;
    }

    public function search($search, $begin = 0, $count = 15, $match_all = false, $match_substrings = false, $languages = null, $modules = null)
    {
        // replace line endings by a space
        $search = preg_replace('/[\n\r]/is', ' ', $search);
        // replace symbols and punctuations
        $search = str_replace(self::$drop_chars, ' ', $search);

        $search = $this->tokenize_search($search);

		$sql_string  = 'SELECT
						  a.module_ident,
						  a.content_ident,
						  a.lang_ident,
						  AVG(a.quality) AS quality,
						  COUNT(a.content_ident) AS count
						FROM
						  data_search_wordmatch AS a
						WHERE
						  a.word_id IN (';

		if (!$match_substrings)
		{
			$sql_string .= 'SELECT
							  b.word_id
							FROM
							  data_search_words AS b
							WHERE
							  b.word IN (\''.implode('\', \'', $search).'\')';
		}
		else
		{
			$sql_string .= 'SELECT
							  b.word_id
							FROM
							  data_search_words as b
								INNER JOIN data_search_word_substrings AS c
								  ON c.word_id = b.word_id
								INNER JOIN data_search_substrings AS d
								  ON d.substring_id = c.substring_id
							WHERE
							  d.substring IN (\''.implode('\', \'', $search).'\')';
		}

		$sql_string .= ')
						GROUP BY
						  a.module_ident,
						  a.content_ident,
						  a.lang_ident,
						  a.quality';
		if ($match_all)
			$sql_string .= ' HAVING COUNT(a.content_ident) = '.count($search);
		$sql_string .= ' ORDER BY AVG(a.quality) ASC, COUNT(a.content_ident) DESC
						LIMIT '.$begin.', '.$count;

		return $sql_string;
    }

	public function index_cleanup()
	{
		$word_ids = $substring_ids = array();

		$sql_string  = 'SELECT
						  a.word_id
						FROM
						  data_search_words AS a
							LEFT JOIN data_search_wordmatch AS b
							  ON b.word_id = a.word_id
						WHERE
						  b.word_id IS NULL';
		$result = $this->unibox->db->query($sql_string, 'failed to get data to clean word index');
		while (list($word_id) = $result->fetch_row())
			$word_ids[] = $word_id;
		$result->free();

		if (!empty($word_ids))
		{
			$sql_string  = 'DELETE FROM
							  data_search_words
							WHERE
							  word_id IN ('.implode(', ', $word_ids).')';
			$this->unibox->db->query($sql_string, 'failed to clean word index');
		}

		$sql_string  = 'SELECT
						  a.substring_id
						FROM
						  data_search_substrings AS a
							LEFT JOIN data_search_word_substrings AS b
							  ON b.substring_id = a.substring_id
						WHERE
						  b.substring_id IS NULL';
		$result = $this->unibox->db->query($sql_string, 'failed to get data to clean substring index');
		while (list($substring_id) = $result->fetch_row())
			$substring_ids[] = $substring_id;
		$result->free();

		if (!empty($substring_ids))
		{
			$sql_string  = 'DELETE FROM
							  data_search_substrings
							WHERE
							  substring_id IN ('.implode(', ', $substring_ids).')';
			$this->unibox->db->query($sql_string, 'failed to clean substring index');
		}
	}
}

?>