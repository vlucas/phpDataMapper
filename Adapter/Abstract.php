<?php
require_once dirname(__FILE__) . '/Interface.php';
/**
 * Abstract Adapter
 *
 * @package phpDataMapper
 * @link http://phpdatamapper.com
 */
abstract class phpDataMapper_Adapter_Abstract
{
	// Format for date columns, formatted for PHP's date() function
	protected $format_date;
	protected $format_time;
	protected $format_datetime;
	
	
	// Connection details
	protected $connection;
	protected $host;
	protected $database;
	protected $username;
	protected $password;
	protected $options;
	
	
	/**
	 * Get database connection
	 * 
	 * @return object Mongo
	 */
	public function connection()
	{
		return $this->connection;
	}
	
	
	/**
	 * Get database format
	 *
	 * @return string Date format for PHP's date() function
	 */
	public function dateFormat()
	{
		return $this->format_date;
	}
	
	
	/**
	 * Get database time format
	 *
	 * @return string Time format for PHP's date() function
	 */
	public function timeFormat()
	{
		return $this->format_time;
	}
	
	
	/**
	 * Get database format
	 *
	 * @return string DateTime format for PHP's date() function
	 */
	public function dateTimeFormat()
	{
		return $this->format_datetime;
	}
	
	
	/**
	 * Get date
	 *
	 * @return object DateTime
	 */
	public function date($format = null)
	{
		if(null === $format) {
			$format = $this->dateFormat();
		}
		return $this->dateTimeObject($format . ' ' . $this->timeFormat());
	}
	
	
	/**
	 * Get database time format
	 *
	 * @return object DateTime
	 */
	public function time($format = null)
	{
		if(null === $format) {
			$format = $this->timeFormat();
		}
		return $this->dateTimeObject($this->dateFormat() . ' ' . $format);
	}
	
	
	/**
	 * Get datetime
	 *
	 * @return object DateTIme
	 */
	public function dateTime($format = null)
	{
		if(null === $format) {
			$format = $this->dateTimeFormat();
		}
		return $this->dateTimeObject($format);
	}
	
	
	/**
	 * Turn formstted date into timestamp
	 * Also handles input timestamps
	 *
	 * @return int Unix timestamp
	 */
	protected function dateTimeObject($format)
	{
		// Alreaady a timestamp?
		if(is_int($format) || is_float($format)) { // @link http://www.php.net/manual/en/function.is-int.php#97006
			return new DateTime('@' . $format); // Timestamps must be prefixed with '@' symbol
		}
		return new DateTime(strtotime($format)); // @todo Change so that it does not depend on a timestamp (in PHP5.3 we can use DateTime::createFromFormat(), but we need 5.2 support for now...)
	}
}