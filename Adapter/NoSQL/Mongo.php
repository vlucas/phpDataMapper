<?php
require_once(dirname(__FILE__) . '/Interface.php');
/**
 * MongoDB NoSQL Adapter
 *
 * @package phpDataMapper
 * @link http://phpdatamapper.com
 * @link http://github.com/vlucas/phpDataMapper
 */
abstract class phpDataMapper_Adapter_NoSQL_Mongo implements phpDataMapper_Adapter_Interface
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
    * @param mixed $host Host string or pre-existing Mongo object
	* @param string $database Optional if $host is Mongo object
    * @param string $username Optional if $host is Mongo object
    * @param string $password Optional if $host is Mongo object
    * @param array $options
    * @return void
    */
    public function __construct($host, $database = null, $username = null, $password = null, array $options = array())
    {
		// Ensure Mongo PHP extension is loaded (required to work)
		if(!extension_loaded('mongo')) {
			throw new phpDataMapper_Exception("MongoDB PHP extension required for this adapter");
		}
		
    	if($host instanceof Mongo) {
    		$this->connection = $host;
    	} else {
			$this->host = $host;
			$this->database = $database;
			$this->username = $username;
			$this->password = $password;
			$this->options = $options;
			
			// Establish connection
			try {
				$this->connection = new Mongo($this->dsn(), $this->options);
			} catch(Exception $e) {
				throw new phpDataMapper_Exception($e->getMessage());
			}
    	}
    }
	
	
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
	 * Get DSN string to connect with
	 * 
	 * @return string
	 */
	public function dsn()
	{
		return 'mongodb://' . $this->username . ':' . $this->password . '@' . $this->host . '/' . $this->database;
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
	 * Migrate structure changes to database
	 * 
	 * @param String $datasource Datasource name
	 * @param Array $fields Fields and their attributes as defined in the mapper
	 */
	public function migrate($datasource, array $fields)
	{
		// @todo Possibly add 'ensureIndex' function calls in case of modified field definitions with added indexes
		return true; // Whoo! NoSQL doesn't have set schema!
	}
	
	
	/**
	 * Create new entity record with set properties
	 */
	public function create($datasource, array $data)
	{
		return false;
	}
	
	
	/**
	 * Build a Mongo query
	 */
	public function read(phpDataMapper_Query $query)
	{
		// Sorting: ASC: 1,  DESC: 2
		// @link http://api.mongodb.org/cplusplus/0.9.2/classmongo_1_1_query.html
		return false;
	}
	
	/**
	 * Update entity
	 */
	public function update($datasource, array $data, array $where = array())
	{
		return false;
	}
	
	
	/**
	 * Delete entities matching given conditions
	 *
	 * @param string $datasource Name of data source
	 * @param array $conditions Array of conditions in column => value pairs
	 */
	public function delete($datasource, array $data)
	{
		return false;
	}
	
	
	/**
	 * Truncate a database table
	 * Should delete all rows and reset serial/auto_increment keys to 0
	 */
	public function truncateDatasource($datasource)
	{
		return false;
	}
	
	
	/**
	 * Drop a database table
	 * Destructive and dangerous - drops entire table and all data
	 */
	public function dropDatasource($datasource)
	{
		return false;
	}
	
	
	/**
	 * Create a database
 	 * Will throw errors if user does not have proper permissions
	 */
	public function createDatabase($database)
	{
		return false;
	}
	
	
	/**
	 * Drop a database table
	 * Destructive and dangerous - drops entire table and all data
	 * Will throw errors if user does not have proper permissions
	 */
	public function dropDatabase($database)
	{
		return false;
	}
	
	
	/**
	 * Return result set for current query
	 */
	public function toCollection(phpDataMapper_Query $query, $stmt)
	{
		return false;
	}
}