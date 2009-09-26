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
	 * Insert given row object with set properties
	 */
	public function insert($table, array $data)
	{
		$binds = $this->getBindsFromConditions($data);
		
		// build the statement
		$sql = "INSERT INTO " . $table .
			" (" . implode(', ', array_keys($data)) . ")" .
			" VALUES(:" . implode(', :', array_keys($binds)) . ")";
		
		// Add query to log
		phpDataMapper_Model::logQuery($sql, $binds);
		
		// Prepare update query
		$stmt = $this->prepare($sql);
		
		if($stmt) {
			// Bind values to columns
			$this->bindValues($stmt, $binds);
			
			// Execute
			if($stmt->execute()) {
				$result = $this->lastInsertId();
			} else {
				$result = false;
			}
		} else {
			$result = false;
		}
		
		return $result;
	}
	
	
	/**
	 * Update given row object
	 */
	public function update($table, array $data, array $where = array())
	{
		// Get "col = :col" pairs for the update query
		$placeholders = array();
		$binds = array();
		foreach($data as $field => $value) {
			$placeholders[] = $field . " = :" . $field . "";
			$binds[$field] = $value;
		}
		
		// Where clauses
		$sqlWheres = array();
		if(count($where) > 0) {
			foreach($where as $wField => $wValue) {
				$sqlWheres[] = $wField . " = :w_" . $wField;
				$binds['w_' . $wField] = $wValue;
			}
		}
		
		// Ensure there are actually updated values on THIS table
		if(count($binds) > 0) {
			// Build the query
			$sql = "UPDATE " . $table .
				" SET " . implode(', ', $placeholders) .
				" WHERE " . implode(' AND ', $sqlWheres);
			
			// Add query to log
			phpDataMapper_Model::logQuery($sql, $binds);
			
			// Prepare update query
			$stmt = $this->prepare($sql);
			
			// Bind column values
			$this->bindValues($stmt, $binds);
			
			if($stmt) {
				// Execute
				if($stmt->execute($binds)) {
					$result = true;
				} else {
					$result = false;
				}
			} else {
				$result = false;
			}
		} else {
			$result = false;
		}
		
		return $result;
	}
	
	
	/**
	 * Delete rows matching given conditions
	 *
	 * @param array $conditions Array of conditions in column => value pairs
	 */
	public function delete($table, array $data)
	{
		$binds = $this->getBindsFromConditions($data);
		
		$sql = "DELETE FROM " . $table . "";
		$sql = $this->getSqlFromConditions($sql, $data);
		$stmt = $this->prepare($sql, $this->getBindsFromConditions($data));
		if($stmt) {
			// Execute
			if($stmt->execute($binds)) {
				$result = true;
			} else {
				$result = false;
			}
		} else {
			$result = false;
		}
		return $result;
	}
	
	
	/**
	 * Truncate a database table
	 * Should delete all rows and reset serial/auto_increment keys to 0
	 */
	public function truncateTable($table) {
		$sql = "TRUNCATE TABLE " . $table;
		return $this->exec($sql);
	}
	
	
	/**
	 * Drop a database table
	 * Destructive and dangerous - drops entire table and all data
	 */
	public function dropTable($table) {
		$sql = "DROP TABLE " . $table;
		return $this->exec($sql);
	}
	
	
	/**
	 * Builds an SQL string given conditions
	 */
	protected function getSqlFromConditions($sql, array $conditions)
	{
		$sqlWhere = array();
		$defaultColOperators = array(0 => '', 1 => '=');
		$ci = 0;
		foreach($conditions as $column => $value) {
			// Column name with comparison operator
			$colData = explode(' ', $column);
			$col = $colData[0];
			
			// Array of values, assume IN clause
			if(is_array($value)) {
				$sqlWhere[] = $col . " IN('" . implode("', '", $value) . "')";
			
			// NULL value
			} elseif(is_null($value)) {
				$sqlWhere[] = $col . " IS NULL";
			
			// Standard string value
			} else {
				$colComparison = isset($colData[1]) ? $colData[1] : '=';
				$columnSql = $col . ' ' . $colComparison;
				
				// Add to binds array and add to WHERE clause
				$colParam = str_replace('.', '_', $col) . $ci;
				$sqlWhere[] = $columnSql . " :" . $colParam . "";
			}
			
			// Increment ensures column name distinction
			$ci++;
		}
		
		$sql .= empty($sqlWhere) ? "" : " WHERE " . implode(' AND ', $sqlWhere);
		return $sql;
	}
	
	
	/**
	 * Returns array of binds to pass to query function
	 */
	protected function getBindsFromConditions(array $conditions)
	{
		$binds = array();
		$ci = 0;
		foreach($conditions as $column => $value) {
			// Can't bind array of values
			if(!is_array($value)) {
				// Column name with comparison operator
				list($col) = explode(' ', $column);
				$colParam = str_replace('.', '_', $col) . $ci;
				
				// Add to binds array and add to WHERE clause
				$binds[$colParam] = $value;
			}
			
			// Increment ensures column name distinction
			$ci++;
		}
		return $binds;
	}
	
	
	/**
	 * Bind array of field/value data to given statement
	 *
	 * @param PDOStatement $stmt
	 * @param array $binds
	 */
	protected function bindValues($stmt, array $binds)
	{
		// Bind each value to the given prepared statement
		foreach($binds as $field => $value) {
			$stmt->bindValue($field, $value);
		}
		return true;
	}
	
	
	/**
	 * PDO/Connection passthrough
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