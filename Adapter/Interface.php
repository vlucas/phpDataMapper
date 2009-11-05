<?php
/**
 * $Id$
 * 
 * @package phpDataMapper
 * @author Vance Lucas <vance@vancelucas.com>
 * @link http://phpdatamapper.com
 */
interface phpDataMapper_Adapter_Interface
{
    /**
    * @param mixed $host Host string or pre-existing PDO object
	* @param string $database Optional if $host is PDO object
    * @param string $username Optional if $host is PDO object
    * @param string $password Optional if $host is PDO object
    * @param array $options
    * @return void
    */
    public function __construct($host, $database = null, $username = null, $password = null, array $options = array());
	
	
	/**
	 *	Get database connection
	 */
	public function getConnection();
	
	
	/**
	 *	Get DSN string for PDO to connect with
	 */
	public function getDsn();
	
	
	/**
	 *	Get database DATE format
	 */
	public function getDateFormat();
	
	
	/**
	 *	Get database full DATETIME
	 */
	public function getDateTimeFormat();
	
	
	/**
	 * Escape/quote direct user input
	 *
	 * @param string $string
	 */
	public function escape($string);
	
	
	/**
	 * Insert row into database
	 */
	public function insert($table, array $data);
	
	
	/**
	 * Update row in database
	 */
	public function update($table, array $data, array $where = array());
	
	
	/**
	 * Delete row from database
	 */
	public function delete($table, array $where);
}