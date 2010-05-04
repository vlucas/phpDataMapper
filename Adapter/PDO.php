<?php
require_once(dirname(__FILE__) . '/Interface.php');
/**
 * Base PDO adapter
 *
 * @package phpDataMapper
 * @link http://phpdatamapper.com
 * @link http://github.com/vlucas/phpDataMapper
 */
abstract class phpDataMapper_Adapter_PDO implements phpDataMapper_Adapter_Interface
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
				$this->connection = new PDO($this->dsn(), $this->username, $this->password, $this->options);
				// Throw exceptions by default
				$this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			/*
			} catch(PDOException $e) {
				if($e->getCode() == 1049) {
					// Database not found, try connection with no db specified
					$this->connection = new PDO($this->getDsn(), $this->username, $this->password, $this->options);
				} else {
					throw new phpDataMapper_Exception($e->getMessage());
				}
			*/
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
	public function connection()
	{
		return $this->connection;
	}
	
	
	/**
	 * Get DSN string for PDO to connect with
	 * 
	 * @return string
	 */
	public function dsn()
	{
		throw new BadMethodCallException("Error: Method " . __FUNCTION__ . " must be defined in the adapter");
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
	 * Escape/quote direct user input
	 *
	 * @param string $string
	 */
	public function escape($string)
	{
		return $this->connection()->quote($string);
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
		$tableColumns = $this->getColumnsForTable($table, $this->database);
		
		if($tableColumns) {
			$tableExists = true;
		}
		if($tableExists) {
			// Update table
			$this->migrateTableUpdate($table, $fields);
		} else {
			// Create table
			$this->migrateTableCreate($table, $fields);
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
		
		// Add query to log
		phpDataMapper_Base::logQuery($sql);
		
		$this->connection()->exec($sql);
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
		$tableColumns = $this->getColumnsForTable($table, $this->database);
		$updateFormattedFields = array();
		foreach($tableColumns as $fieldName => $columnInfo) {
			if(isset($formattedFields[$fieldName])) {
				// TODO: Need to do a more exact comparison and make this non-mysql specific
				if ( 
						$this->_fieldTypeMap[$formattedFields[$fieldName]['type']] != $columnInfo['DATA_TYPE'] ||
						$formattedFields[$fieldName]['default'] !== $columnInfo['COLUMN_DEFAULT']
					) {
					$updateFormattedFields[$fieldName] = $formattedFields[$fieldName];
				}
				
				unset($formattedFields[$fieldName]);
			}
		}
		
		$columnsSyntax = array();
		// Update fields whose options have changed
		foreach($updateFormattedFields as $fieldName => $fieldInfo) {
			$columnsSyntax[$fieldName] = $this->migrateSyntaxFieldUpdate($fieldName, $fieldInfo, false);
		}
		// Add fields that are missing from current ones
		foreach($formattedFields as $fieldName => $fieldInfo) {
			$columnsSyntax[$fieldName] = $this->migrateSyntaxFieldUpdate($fieldName, $fieldInfo, true);
		}
		
		// Get syntax for table with fields/columns
		if ( !empty($columnsSyntax) ) {
			$sql = $this->migrateSyntaxTableUpdate($table, $formattedFields, $columnsSyntax);
			
			// Add query to log
			phpDataMapper_Base::logQuery($sql);
			
			// Run SQL
			$this->connection()->exec($sql);
		}
		return true;
	}
	
	
	/**
	 * Prepare an SQL statement 
	 */
	public function prepare($sql)
	{
		return $this->connection()->prepare($sql);
	}
	
	/**
	 * Create new row object with set properties
	 */
	public function create($source, array $data)
	{
		$binds = $this->statementBinds($data);
		// build the statement
		$sql = "INSERT INTO " . $source .
			" (" . implode(', ', array_keys($data)) . ")" .
			" VALUES(:" . implode(', :', array_keys($binds)) . ")";
		
		// Add query to log
		phpDataMapper_Base::logQuery($sql, $binds);
		
		// Prepare update query
		$stmt = $this->connection()->prepare($sql);
		
		if($stmt) {
			// Execute
			if($stmt->execute($binds)) {
				$result = $this->connection()->lastInsertId();
			} else {
				$result = false;
			}
		} else {
			$result = false;
		}
		
		return $result;
	}
	
	
	/**
	 * Build a select statement in SQL
	 * Can be overridden by adapters for custom syntax
	 *
	 * @todo Add support for JOINs
	 */
	public function read(phpDataMapper_Query $query)
	{
		$conditions = $this->statementConditions($query->conditions);
		$binds = $this->statementBinds($query->params());
		$order = array();
		if($query->order) {
			foreach($query->order as $oField => $oSort) {
				$order[] = $oField . " " . $oSort;
			}
		}
		
		$sql = "
			SELECT " . $this->statementFields($query->fields) . "
			FROM " . $query->source . "
			" . ($conditions ? 'WHERE ' . $conditions : '') . "
			" . ($query->group ? 'GROUP BY ' . implode(', ', $query->group) : '') . "
			" . ($order ? 'ORDER BY ' . implode(', ', $order) : '') . "
			" . ($query->limit ? 'LIMIT ' . $query->limit : '') . " " . ($query->limit && $query->limitOffset ? 'OFFSET ' . $query->limitOffset: '') . "
			";
		
		// Unset any NULL values in binds (compared as "IS NULL" and "IS NOT NULL" in SQL instead)
		if($binds && count($binds) > 0) {
			foreach($binds as $field => $value) {
				if(null === $value) {
					unset($binds[$field]);
				}
			}
		}
		
		// Add query to log
		phpDataMapper_Base::logQuery($sql, $binds);
		
		// Prepare update query
		$stmt = $this->connection()->prepare($sql);
		
		if($stmt) {
			// Execute
			if($stmt->execute($binds)) {
				$result = $this->toCollection($query, $stmt);
			} else {
				$result = false;
			}
		} else {
			$result = false;
		}
		
		return $result;
	}
	
	/**
	 * Update entity
	 */
	public function update($source, array $data, array $where = array())
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
			$sql = "UPDATE " . $source .
				" SET " . implode(', ', $placeholders) .
				" WHERE " . implode(' AND ', $sqlWheres);
			
			// Add query to log
			phpDataMapper_Base::logQuery($sql, $binds);
			
			// Prepare update query
			$stmt = $this->connection()->prepare($sql);
			
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
	 * Delete entities matching given conditions
	 *
	 * @param string $source Name of data source
	 * @param array $conditions Array of conditions in column => value pairs
	 */
	public function delete($source, array $data)
	{
		$binds = $this->statementBinds($data);
		$conditions = $this->statementConditions($data);
		
		$sql = "DELETE FROM " . $source . "";
		$sql .= ($conditions ? ' WHERE ' . $conditions : '');
		
		// Add query to log
		phpDataMapper_Base::logQuery($sql, $binds);
		
		$stmt = $this->connection()->prepare($sql);
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
	public function truncateDatasource($source) {
		$sql = "TRUNCATE TABLE " . $source;
		
		// Add query to log
		phpDataMapper_Base::logQuery($sql);
		
		return $this->connection()->exec($sql);
	}
	
	
	/**
	 * Drop a database table
	 * Destructive and dangerous - drops entire table and all data
	 */
	public function dropDatasource($source) {
		$sql = "DROP TABLE " . $source;
		
		// Add query to log
		phpDataMapper_Base::logQuery($sql);
		
		return $this->connection()->exec($sql);
	}
	
	
	/**
	 * Create a database
 	 * Will throw errors if user does not have proper permissions
	 */
	public function createDatabase($database) {
		$sql = "CREATE DATABASE " . $database;
		
		// Add query to log
		phpDataMapper_Base::logQuery($sql);
		
		return $this->connection()->exec($sql);
	}
	
	
	/**
	 * Drop a database table
	 * Destructive and dangerous - drops entire table and all data
	 * Will throw errors if user does not have proper permissions
	 */
	public function dropDatabase($database) {
		$sql = "DROP DATABASE " . $database;
		
		// Add query to log
		phpDataMapper_Base::logQuery($sql);
		
		return $this->connection()->exec($sql);
	}
	
	
	/**
	 * Return fields as a string for a query statement
	 */
	public function statementFields(array $fields = array())
	{
		return count($fields) > 0 ? implode(', ', $fields) : "*";
	}
	
	
	/**
	 * Builds an SQL string given conditions
	 */
	public function statementConditions(array $conditions = array())
	{
		if(count($conditions) == 0) { return; }
		
		$sqlStatement = "";
		$defaultColOperators = array(0 => '', 1 => '=');
		$ci = 0;
		$loopOnce = false;
		foreach($conditions as $condition) {
			if(is_array($condition) && isset($condition['conditions'])) {
				$subConditions = $condition['conditions'];
			} else {
				$subConditions = $conditions;
				$loopOnce = true;
			}
			$sqlWhere = array();
			foreach($subConditions as $column => $value) {
				// Column name with comparison operator
				$colData = explode(' ', $column);
				if ( count( $colData ) > 2 ) {
					$operator = array_pop( $colData );
					$colData = array( join(' ', $colData), $operator );
				}
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
					$colParam = preg_replace('/\W+/', '_', $col) . $ci;
					$sqlWhere[] = $columnSql . " :" . $colParam . "";
				}
				
				// Increment ensures column name distinction
				$ci++;
			}
			if ( $sqlStatement != "" ) {
				$sqlStatement .= " " . (isset($condition['setType']) ? $condition['setType'] : 'AND') . " ";
			}
			//var_dump($condition);
			$sqlStatement .= join(" " . (isset($condition['type']) ? $condition['type'] : 'AND') . " ", $sqlWhere );
			
			if($loopOnce) { break; }
		}
		
		return $sqlStatement;
	}
	
	
	/**
	 * Returns array of binds to pass to query function
	 */
	public function statementBinds(array $conditions = array())
	{
		if(count($conditions) == 0) { return; }
		
		$binds = array();
		$ci = 0;
		$loopOnce = false;
		foreach($conditions as $condition) {
			if(is_array($condition) && isset($condition['conditions'])) {
				$subConditions = $condition['conditions'];
			} else {
				$subConditions = $conditions;
				$loopOnce = true;
			}
			foreach($subConditions as $column => $value) {
				// Can't bind array of values
				if(!is_array($value) && !is_object($value)) {
					// Column name with comparison operator
					$colData = explode(' ', $column);
					if ( count( $colData ) > 2 ) {
						$operator = array_pop( $colData );
						$colData = array( join(' ', $colData), $operator );
					}
					$col = $colData[0];
					$colParam = preg_replace('/\W+/', '_', $col) . $ci;
					
					// Add to binds array and add to WHERE clause
					$binds[$colParam] = $value;
				}
				
				// Increment ensures column name distinction
				$ci++;
			}
			if($loopOnce) { break; }
		}
		return $binds;
	}
	
	
	/**
	 * Return result set for current query
	 */
	public function toCollection(phpDataMapper_Query $query, $stmt)
	{
		$mapper = $query->mapper();
		if($stmt instanceof PDOStatement) {
			$results = array();
			$resultsIdentities = array();
			
			// Set object to fetch results into
			$stmt->setFetchMode(PDO::FETCH_CLASS, $mapper->entityClass());
			
			// Fetch all results into new DataMapper_Result class
			while($row = $stmt->fetch(PDO::FETCH_CLASS)) {
				
				// Load relations for this row
				$relations = $mapper->getRelationsFor($row);
				if($relations && is_array($relations) && count($relations) > 0) {
					foreach($relations as $relationCol => $relationObj) {
						$row->$relationCol = $relationObj;
					}
				}
				
				// Store in array for ResultSet
				$results[] = $row;
				
				// Store primary key of each unique record in set
				$pk = $mapper->primaryKey($row);
				if(!in_array($pk, $resultsIdentities) && !empty($pk)) {
					$resultsIdentities[] = $pk;
				}
				
				// Mark row as loaded
				$row->loaded(true);
			}
			// Ensure set is closed
			$stmt->closeCursor();
			
			$collectionClass = $mapper->collectionClass();
			return new $collectionClass($results, $resultsIdentities);
			
		} else {
			$mapper->addError(__METHOD__ . " - Unable to execute query " . implode(' | ', $this->adapterRead()->errorInfo()));
			return array();
		}
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
}