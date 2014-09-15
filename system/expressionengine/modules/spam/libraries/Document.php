<?php
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Spam Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

/**
 * Document class. Cleans and generates a frequency table of a document.
 * 
 * @implements Iterator
 */
class Document implements Iterator {

	public $frequency = array();
	public $words = array();
	public $max_frequency = 0;
	private $position = 0;
	
	/**
	 * Clean the text, and then generate the frequency table.
	 * 
	 * @access public
	 * @param mixed   $text The text of the Document we are getting the frequencies for
	 * @param string  $tokenizer  Tokenize by words or characters
	 * @param int     $ngram  The n-gram to calculate
	 * @param bool    $clean  Strip all non alpha-numeric characters
	 * @return void
	 */
	public function __construct($text, $tokenizer = 'words', $ngram = 1, $clean = TRUE)
	{
		if ($clean === TRUE)
		{
			$text = preg_replace("/[^a-zA-Z0-9\s]/", "", $text);
		}

		$text = trim($text);
		$this->tokenizer = $tokenizer;
		$this->ngram = $ngram;
		$this->text = $text;
		$this->frequency = $this->_frequency($text);
		$this->words = array_keys($this->frequency);
		$this->size = count(explode(' ',$text));
	}
	
	/**
	 * We override __invoke here to make the frequency easily callable.
	 * 
	 * @access public
	 * @param string $word The word you want the frequency of
	 * @return float
	 */
	public function __invoke($word)
	{
		return $this->frequency($word);
	}
	
	/**
	 * Return the frequency of a word.
	 * 
	 * @access public
	 * @param string $word The word you want the frequency of
	 * @return float
	 */
	public function frequency($word)
	{
		if (empty($this->frequency[$word]))
		{
			return 0;
		}
		else
		{
			return $this->frequency[$word];
		}
	}

	/**
	 * Count and rank the frequency of words
	 * 
	 * @access private
	 * @param mixed $text
	 * @return array
	 */
	private function _frequency($text)
	{
		$count = array();

		if ($this->tokenizer == 'words')
		{
			$words = preg_split('/\s+/', $text);
		}
		elseif ($this->tokenizer == 'charcters')
		{
			$words = str_split($text);
		}

		$words = $this->ngrams($words, $this->ngram);

		$num = count($words);
		$max = 0;

		foreach ($words as $word)
		{
			$words = implode('', $word);
			$word = strtolower($word);

			if (isset($count[$word]))
			{
				$count[$word]++;
			}
			else
			{
				$count[$word] = 1;
			}

			$max = max($max, $count[$word]);
		}

		$this->max_frequency = $max;
		arsort($count);
		return $count; 
	}

	public function rewind()
	{
		$this->position = 0;
	}

	public function current()
	{
		return $this->frequency[$this->words[$this->position]];
	}

	public function key()
	{
		return $this->words[$this->position];
	}

	public function next()
	{
		++$this->position;
	}

	public function valid()
	{
		return isset($this->words[$this->position]);
	}
	
}
// END CLASS

/* End of file Document.php */
/* Location: ./system/expressionengine/modules/spam/libraries/Document.php */
