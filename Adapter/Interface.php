<?php
/**
 * Adapter Interface
 * 
 * @package phpDataMapper
 * @link http://phpdatamapper.com
 */
interface phpDataMapper_Adapter_Interface
{
    /**
    * @param mixed $host Host string or pre-existing PDO object
	* @param string $datasource Optional if $host is PDO object
    * @param string $username Optional if $host is PDO object
    * @param string $password Optional if $host is PDO object
    * @param array $options
    * @return void
    */
    public function __construct($host, $datasource = null, $username = null, $password = null, array $options = array());
	
	
	/**
	 * Get database connection
	 */
	public function getConnection();
	
	
	/**
	 * Get database DATE format for PHP date() function
	 */
	public function getDateFormat();
	
	
	/**
	 * Get database full DATETIME for PHP date() function
	 */
	public function getDateTimeFormat();
	
	
	/**
	 * Escape/quote direct user input
	 *
	 * @param string $string
	 */
	public function escape($string);
	
	
	/**
	 * Insert entity
	 */
	public function create($source, array $data);
	
	
	/**
	 * Read from data source using given query object
	 */
	public function read(phpDataMapper_Query $query);
	
	
	/**
	 * Update entity
	 */
	public function update($source, array $data, array $where = array());
	
	
	/**
	 * Delete entity
	 */
	public function delete($source, array $where);
}