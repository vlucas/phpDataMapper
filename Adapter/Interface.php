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
	public function connection();
	
	
	/**
	 * Get database DATE format for PHP date() function
	 */
	public function dateFormat();
	
	
	/**
	 * Get database TIME format for PHP date() function
	 */
	public function timeFormat();
	
	
	/**
	 * Get database full DATETIME for PHP date() function
	 */
	public function dateTimeFormat();
	
	
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
	
	
	/**
	 * Truncate data source (table for SQL)
	 * Should delete all rows and reset serial/auto_increment keys to 0
	 */
	public function truncateDatasource($source);
	
	/**
	 * Drop/delete data source (table for SQL)
	 * Destructive and dangerous - drops entire data source and all data
	 */
	public function dropDatasource($source);
	
	
	/**
	 * Create a database
 	 * Will throw errors if user does not have proper permissions
	 */
	public function createDatabase($database);
	
	
	/**
	 * Drop an entire database
	 * Destructive and dangerous - drops entire table and all data
	 * Will throw errors if user does not have proper permissions
	 */
	public function dropDatabase($database);
}