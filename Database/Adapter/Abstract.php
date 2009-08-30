<?php
require_once(dirname(__FILE__) . '/Interface.php');
/**
 * $Id$
 *
 * @package phpDataMapper
 * @author Vance Lucas <vance@vancelucas.com>
 * @link http://phpdatamapper.com
 */
abstract class phpDataMapper_Database_Adapter_Abstract implements phpDataMapper_Database_Adapter_Interface
{
	// Format for date columns, formatted for PHP's date() function
	protected $FORMAT_DATE;
	protected $FORMAT_DATETIME;
	
	
	// Connection details
	protected $connection;
	protected $host;
	protected $database;
	protected $username;
	protected $password;
	protected $options;
	
	
    /**
    * @param mixed $host Host string or pre-existing PDO object
	* @param string $database Optional if $host is PDO object
    * @param string $username Optional if $host is PDO object
    * @param string $password Optional if $host is PDO object
    * @param array $options
    * @return void
    */
    public function __construct($host, $database = null, $username = null, $password = null, array $options = array())
    {
    	if($host instanceof PDO) {
    		$this->connection = $host;
    	} else {
			$this->host = $host;
			$this->database = $database;
			$this->username = $username;
			$this->password = $password;
			$this->options = $options;
			
			// Establish connection
			try {
				$this->connection = new PDO($this->getDsn(), $this->username, $this->password, $this->options);
			} catch(Exception $e) {
				throw new phpDataMapper_Exception($e->getMessage());
			}
    	}
    }
	
	
	/**
	 * Get database connection
	 * 
	 * @return object PDO
	 */
	public function getConnection()
	{
		return $this->connection;
	}
	
	
	/**
	 * Get DSN string for PDO to connect with
	 * 
	 * @return string
	 */
	public function getDsn()
	{
		throw new BadMethodCallException("Error: Method " . __FUNCTION__ . " must be defined in the adapter");
	}
	
	
	/**
	 * Get database format
	 *
	 * @return string Date format for PHP's date() function
	 */
	public function getDateFormat()
	{
		return $this->format_date;
	}
	
	
	/**
	 * Get database format
	 *
	 * @return string DateTime format for PHP's date() function
	 */
	public function getDateTimeFormat()
	{
		return $this->format_datetime;
	}
	
	
	/**
	 * Escape/quote direct user input
	 *
	 * @param string $string
	 */
	public function escape($string)
	{
		return $this->connection->quote($string);
	}
	
	
	/**
	 * Format fields with all neccesary array keys and values. Merges defaults with specified values to ensure all options exist for each field.
	 *
	 * @param array $fields Field definitions from Model
	 * @return array Given fields plus all defaults for full array of all possible options
	 */
	protected function formatFields(array $fields)
	{
		// Default settings for all fields
		$fieldDefaults = array(
			'type' => 'string',
			'default' => null,
			'length' => null,
			'required' => false,
			'null' => true,
			
			'auto_increment' => false,
			'primary' => false,
			'index' => false,
			'unique' => false
			);
		
		// Type default overrides for specific field types
		$fieldTypeDefaults = array(
			'string' => array(
				'length' => 255
				),
			'float' => array(
				'length' => array(10,2)
				),
			'int' => array(
				'length' => 10,
				'unsigned' => true
				)
			);
		
		// Iterate over all fields
		$formattedFields = array();
		foreach($fields as $fieldName => $fieldInfo) {
			// Set the 'adapter_type' for SQL syntax
			if(isset($this->fieldTypeMap[$fieldInfo['type']])) {
				$fieldInfo['adapter_type'] = $this->fieldTypeMap[$fieldInfo['type']];
			} else {
				$fieldInfo['adapter_type'] = $fieldInfo['type'];
			}
			
			if(isset($fieldInfo['type']) && isset($fieldTypeDefaults[$fieldInfo['type']])) {
				// Include type defaults
				$formattedFields[$fieldName] = array_merge($fieldDefaults, $fieldTypeDefaults[$fieldInfo['type']], $fieldInfo);
			} else {
				// Merge with defaults
				$formattedFields[$fieldName] = array_merge($fieldDefaults, $fieldInfo);
			}
		}
		return $formattedFields;
	}
	
	
	/**
	 * Migrate table structure changes to database
	 * @param String $table Table name
	 * @param Array $fields Fields and their attributes as defined in the mapper
	 */
	public function migrate($table, array $fields)
	{
		// Get current fields for table
		$tableExists = false;
		$tableColumns = $this->getColumnsForTable($table);
		if($tableColumns) {
			$tableExists = true;
		}
		
		// Get fields with full array of options
		$formattedFields = $this->formatFields($fields);
		
		if($tableExists) {
			// Determine missing or changed columns, if any
			//var_dump($tableColumns);
			
			// Update table
			$this->migrateTableUpdate($table, $formattedFields);
		} else {
			// Create table
			$this->migrateTableCreate($table, $formattedFields);
		}
	}
	
	
	/**
	 * Execute a CREATE TABLE command
	 */
	public function migrateTableCreate($table, array $formattedFields)
	{
		/*
			STEPS:
			* Use fields to get column syntax
			* Use column syntax array to get table syntax
			* Run SQL
		*/
		
		// Prepare fields and get syntax for each
		$columnsSyntax = array();
		foreach($formattedFields as $fieldName => $fieldInfo) {
			$columnsSyntax[$fieldName] = $this->migrateSyntaxFieldCreate($fieldName, $fieldInfo);
		}
		
		// Get syntax for table with fields/columns
		$sql = $this->migrateSyntaxTableCreate($table, $formattedFields, $columnsSyntax);
		// Run SQL
		$this->getConnection()->exec($sql);
		return true;
	}
	
	
	/**
	 * Execute an ALTER/UPDATE TABLE command
	 */
	public function migrateTableUpdate($table, array $formattedFields)
	{
		/*
			STEPS:
			* Use fields to get column syntax
			* Use column syntax array to get table syntax
			* Run SQL
		*/
		
		// Prepare fields and get syntax for each
		$columnsSyntax = array();
		foreach($formattedFields as $fieldName => $fieldInfo) {
			$columnsSyntax[$fieldName] = $this->migrateSyntaxFieldCreate($fieldName, $fieldInfo);
		}
		
		// Get syntax for table with fields/columns
		$sql = $this->migrateSyntaxTableCreate($table, $formattedFields, $columnsSyntax);
		// Run SQL
		$this->getConnection()->exec($sql);
		return true;
	}
	
	
	/**
	 * Compare an associative array recursively
	 */
	public function arrayCompare($array1, $array2)
	{
		foreach($array1 as $key => $value)
		{
			if(is_array($value))
			{
				  if(!isset($array2[$key]))
				  {
					  $difference[$key] = $value;
				  }
				  elseif(!is_array($array2[$key]))
				  {
					  $difference[$key] = $value;
				  }
				  else
				  {
					  $new_diff = $this->{__FUNCTION__}($value, $array2[$key]);
					  if($new_diff != FALSE)
					  {
							$difference[$key] = $new_diff;
					  }
				  }
			  }
			  elseif(!isset($array2[$key]) || $array2[$key] != $value)
			  {
				  $difference[$key] = $value;
			  }
		}
		return !isset($difference) ? 0 : $difference;
	} 
	
	
	/**
	 * PDO passthrough
	 */
	public function __call($func, $args)
	{
		if(is_callable(array($this->getConnection(), $func))) {
			return call_user_func_array(array($this->getConnection(), $func), $args);
		} else {
			return false;
		}
	}
}