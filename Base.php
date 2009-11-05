<?php
/**
 * Base DataMapper Model
 * 
 * @package phpDataMapper
 * @author Vance Lucas <vance@vancelucas.com>
 * @link http://phpdatamapper.com
 */
class phpDataMapper_Base
{
	// Stored adapter connections
	protected $adapter;
	protected $adapterSlave;
	
	// Class Names for required classes - Here so they can be easily overridden
	protected $rowClass = 'phpDataMapper_Row';
	protected $queryClass = 'phpDataMapper_Query';
	protected $resultSetClass = 'phpDataMapper_Query_ResultSet';
	protected $exceptionClass = 'phpDataMapper_Exception';
	protected $validationClass = 'phpDataMapper_Validation';
	
	// Table setup info
	protected $table;
	protected $fields = array();
	protected $primaryKey;
	/**
	 // EXAMPLE 'fields' definition: 
	protected $fields = array(
		'id' => array('type' => 'int', 'primary' => true)
		);
	*/
	protected $relations = array();
	/**
	 // Relationship associations
	    protected $relations = array(
	        // Comments
	        'comments' => array(
	            'relation' => 'HasMany',
	            'mapper' => 'CommentsModel',
	            'foreign_keys' => array('parent' => 'module_id', 'id' => 'module_item_id'),
				)
			);
	*/
	
	// Class loader instance and action name
	protected $loader;
	protected $loaderAction;
	
	// Array of error messages and types
	protected $errors = array();
	
	
	/**
	 *	Constructor Method
	 */
	public function __construct(phpDataMapper_Adapter_Interface $adapter, $adapterSlave = null)
	{
		$this->adapter = $adapter;
		
		// Ensure required classes for minimum activity are loaded
		$this->loadClass($this->rowClass);
		$this->loadClass($this->queryClass);
		$this->loadClass($this->resultSetClass);
		$this->loadClass($this->exceptionClass);
		
		// Slave adapter if given (usually for reads)
		if(null !== $adapterSlave) {
			if($adapterSlave instanceof phpDataMapper_Adapter_Interface) {
				$this->adapterSlave = $adapterSlave;
			} else {
				throw new InvalidArgumentException("Slave adapter must be an instance of 'phpDataMapper_Database_Adapter_Interface'");
			}
		}
		
		// Ensure table has been defined
		if(!$this->table) {
			throw new $this->exceptionClass("Error: Table name must be defined - please define \$table variable.");
		}
		
		// Ensure fields have been defined for current table
		if(!$this->fields || !is_array($this->fields)) {
			throw new $this->exceptionClass("Error: Fields for current table must be defined");
		}
		
		// Find and store primary key field
		foreach($this->fields as $field => $options) {
			if(array_key_exists('primary', $options)) {
				$this->primaryKey = $field;
			}
		}
	}
	
	
	/**
	 * Get current adapter object
	 */
	public function getAdapter()
	{
		return $this->adapter;
	}
	
	
	/**
	 * Get value of primary key for given row result
	 */
	public function getTable()
	{
		return $this->table;
	}
	
	
	/**
	 * Get value of primary key for given row result
	 */
	public function getPrimaryKey(phpDataMapper_Row $row)
	{
		$pkField = $this->getPrimaryKeyField();
		return $row->$pkField;
	}
	
	
	/**
	 * Get value of primary key for given row result
	 */
	public function getPrimaryKeyField()
	{
		return $this->primaryKey;
	}
	
	
	/**
	 * Check if field exists in defined fields
	 */
	public function fieldExists($field)
	{
		return array_key_exists($field, $this->fields);
	}
	
	
	/**
	 * Load record from primary key
	 */
	public function get($primaryKeyValue = 0)
	{
		// Create new row object
		if(!$primaryKeyValue) {
			$row = new $this->rowClass();
		
		// Find record by primary key
		} else {
			$row = $this->first(array($this->getPrimaryKeyField() => $primaryKeyValue));
		}
		return $row;
	}
	
	
	/**
	 * Load defined relations 
	 */
	public function getRelationsFor(phpDataMapper_Row $row)
	{
		$relatedColumns = array();
		if(is_array($this->relations) && count($this->relations) > 0) {
			foreach($this->relations as $column => $relation) {
				$mapperName = $relation['mapper'];
				// Ensure related mapper can be loaded
				if($loaded = $this->loadClass($mapperName)) {
					// Load foreign keys with data from current row
					$foreignKeys = array_flip($relation['foreign_keys']);
					foreach($foreignKeys as $relationCol => $col) {
						$foreignKeys[$relationCol] = $row->$col;
					}
					
					// Create new instance of mapper
					$mapper = new $mapperName($this->adapter);
					
					// Load relation class
					$relationClass = 'phpDataMapper_Relation_' . $relation['relation'];
					if($loadedRel = $this->loadClass($relationClass)) {
						// Set column equal to relation class instance
						$relationObj = new $relationClass($mapper, $foreignKeys, $relation);
						$relatedColumns[$column] = $relationObj;
					}
				}
			}
		}
		return (count($relatedColumns) > 0) ? $relatedColumns : false;
	}
	
	
	/**
	 * Get result set for given PDO Statement
	 */
	public function getResultSet($stmt)
	{
		if($stmt instanceof PDOStatement) {
			$results = array();
			$resultsIdentities = array();
			
			// Set object to fetch results into
			$stmt->setFetchMode(PDO::FETCH_CLASS, $this->rowClass, array());
			
			// Fetch all results into new DataMapper_Result class
			while($row = $stmt->fetch(PDO::FETCH_CLASS)) {
				
				// Load relations for this row
				$relations = $this->getRelationsFor($row);
				if($relations && is_array($relations) && count($relations) > 0) {
					foreach($relations as $relationCol => $relationObj) {
						$row->$relationCol = $relationObj;
					}
				}
				
				// Store in array for ResultSet
				$results[] = $row;
				
				// Store primary key of each unique record in set
				$pk = $this->getPrimaryKey($row);
				if(!in_array($pk, $resultsIdentities) && !empty($pk)) {
					$resultsIdentities[] = $pk;
				}
				
				// Mark row as loaded
				$row->loaded(true);
			}
			// Ensure set is closed
			$stmt->closeCursor();
			
			return new $this->resultSetClass($results, $resultsIdentities);
			
		} else {
			return array();
			//throw new $this->exceptionClass(__METHOD__ . " expected PDOStatement object");
		}
	}
	
	
	/**
	 * Find records with given conditions
	 * If all parameters are empty, find all records
	 *
	 * @param array $conditions Array of conditions in column => value pairs
	 * @param array $orderBy Array of ORDER BY columns/values
	 */
	public function all(array $conditions = array(), array $orderBy = array())
	{
		return $this->select()->where($conditions)->orderBy($orderBy);
	}
	
	
	/**
	 * Find first record matching given conditions
	 *
	 * @param array $conditions Array of conditions in column => value pairs
	 * @param array $orderBy Array of ORDER BY columns/values
	 */
	public function first(array $conditions = array(), array $orderBy = array())
	{
		$query = $this->select()->where($conditions)->orderBy($orderBy)->limit(1);
		$rows = $this->query($query->sql(), $query->getParameters());
		if($rows) {
			return $rows->first();
		} else {
			return false;
		}
	}
	
	
	/**
	 * Find records with custom SQL query
	 *
	 * @param string $sql SQL query to execute
	 * @param array $binds Array of bound parameters to use as values for query
	 * @throws phpDataMapper_Exception
	 */
	public function query($sql, array $binds = array())
	{
		// Add query to log
		self::logQuery($sql, $binds);
		
		// Prepare and execute query
		if($stmt = $this->adapter->prepare($sql)) {
			$results = $stmt->execute($binds);
			if($results) {
				$r = $this->getResultSet($stmt);
			} else {
				$r = false;
			}
			
			return $r;
		} else {
			throw new $this->exceptionClass(__METHOD__ . " Error: Unable to execute SQL query - failed to create prepared statement from given SQL");
		}
		
	}
	
	
	/**
	 * Begin a new database query - get query builder
	 * Acts as a kind of factory to get the current adapter's query builder object
	 * 
	 * @param mixed $fields String for single field or array of fields
	 */
	public function select($fields = "*")
	{
		$adapterName = get_class($this->adapter);
		$adapterClass = $adapterName . "_Query";
		if($this->loadClass($adapterClass)) {
			$query = new $this->queryClass($this->adapter);
			$query->select($fields, $this->table);
			return $query;
		} else {
			throw new $this->exceptionClass(__METHOD__ . " Error: Unable to load new query builder for adapter: '" . $adapterName . "'");
		}
	}
	
	
	/**
	 * Save related rows of data
	 */
	protected function saveRelatedRowsFor($row, array $fillData = array())
	{
		$relationColumns = $this->getRelationsFor($row);
		foreach($row->getData() as $field => $value) {
			if($relationColumns && array_key_exists($field, $relationColumns) && (is_array($value) || is_object($value))) {
				foreach($value as $relatedRow) {
					// Determine relation object
					if($value instanceof phpDataMapper_Relation) {
						$relatedObj = $value;
					} else {
						$relatedObj = $relationColumns[$field];
					}
					$relatedMapper = $relatedObj->getMapper();
					
					// Row object
					if($relatedRow instanceof phpDataMapper_Row) {
						$relatedRowObj = $relatedRow;
						
					// Associative array
					} elseif(is_array($relatedRow)) {
						$relatedRowObj = new $this->rowClass($relatedRow);
					}
					
					// Set column values on row only if other data has been updated (prevents queries for unchanged existing rows)
					if(count($relatedRowObj->getDataModified()) > 0) {
						$fillData = array_merge($relatedObj->getForeignKeys(), $fillData);
						$relatedRowObj->setData($fillData);
					}
					
					// Save related row
					$relatedMapper->save($relatedRowObj);
				}
			}
		}
	}
	
	
	/**
	 * Save result object
	 */
	public function save(phpDataMapper_Row $row)
	{
		// Run validation
		if($this->validate($row)) {
			$pk = $this->getPrimaryKey($row);
			// No primary key, insert
			if(empty($pk)) {
				$result = $this->insert($row);
			// Has primary key, update
			} else {
				$result = $this->update($row);
			}
		} else {
			$result = false;
		}
		
		return $result;
	}
	
	
	/**
	 * Insert given row object with set properties
	 */
	public function insert(phpDataMapper_Row $row)
	{
		$data = array();
		$rowData = $row->getData();
		foreach($rowData as $field => $value) {
			if($this->fieldExists($field)) {
				// Empty values will be NULL (easier to be handled by databases)
				$data[$field] = $this->isEmpty($value) ? null : $value;
			}
		}
		
		// Ensure there is actually data to update
		if(count($data) > 0) {
			$result = $this->adapter->insert($this->getTable(), $data);
			// Update primary key on row
			$pkField = $this->getPrimaryKeyField();
			$row->$pkField = $result;
		} else {
			$result = false;
		}
		
		// Save related rows
		if($result) {
			$this->saveRelatedRowsFor($row);
		}
		
		return $result;
	}
	
	
	/**
	 * Update given row object
	 */
	public function update(phpDataMapper_Row $row)
	{
		// Ensure fields exist to prevent errors
		$binds = array();
		foreach($row->getDataModified() as $field => $value) {
			if($this->fieldExists($field)) {
				// Empty values will be NULL (easier to be handled by databases)
				$binds[$field] = $this->isEmpty($value) ? null : $value;
			}
		}
		
		// Handle with adapter
		$result = $this->adapter->update($this->getTable(), $binds, array($this->getPrimaryKeyField() => $this->getPrimaryKey($row)));
		
		// Save related rows
		if($result) {
			$this->saveRelatedRowsFor($row);
		}
		
		return $result;
	}
	
	
	/**
	 * Destroy/Delete given row object
	 */
	public function destroy(phpDataMapper_Row $row)
	{
		$conditions = array($this->getPrimaryKeyField() => $this->getPrimaryKey($row));
		return $this->delete($conditions);
	}
	
	
	/**
	 * Delete rows matching given conditions
	 *
	 * @param array $conditions Array of conditions in column => value pairs
	 */
	public function delete(array $conditions)
	{
		return $this->adapter->delete($this->table, $conditions);
	}
	
	
	/**
	 * Truncate a database table
	 * Should delete all rows and reset serial/auto_increment keys to 0
	 */
	public function truncateTable() {
		return $this->adapter->truncateTable($this->getTable());
	}
	
	
	/**
	 * Drop a database table
	 * Destructive and dangerous - drops entire table and all data
	 */
	public function dropTable() {
		return $this->adapter->dropTable($this->getTable());
	}
	
	
	/**
	 * Run set validation rules on fields
	 * 
	 * @todo A LOT more to do here... More validation, break up into classes with rules, etc.
	 */
	public function validate(phpDataMapper_Row $row)
	{
		// Check validation rules on each feild
		foreach($this->fields as $field => $fieldAttrs) {
			if(isset($fieldAttrs['required']) && true === $fieldAttrs['required']) {
				// Required field
				if(empty($row->$field)) {
					$this->addError("Required field '" . $field . "' was left blank");
				}
			}
		}
		
		// Check for errors
		if($this->hasErrors()) {
			return false;
		} else {
			return true;
		}
	}
	
	
	/**
	 * Migrate table structure changes from model to database
	 */
	public function migrate()
	{
		return $this->getAdapter()->migrate($this->getTable(), $this->fields);
	}
	
	
	/**
	 * Check if a value is empty, excluding 0 (annoying PHP issue)
	 *
	 * @param mixed $value
	 * @return boolean
	 */
	public function isEmpty($value)
	{
		return (empty($value) && 0 !== $value);
	}
	
	
	/**
	 * Check if any errors exist
	 * 
	 * @return boolean
	 */
	public function hasErrors()
	{
		return count($this->errors);
	}
	
	
	/**
	 *	Get array of error messages
	 *
	 * @return array
	 */
	public function getErrors()
	{
		return $this->errors;
	}
	
	
	/**
	 *	Add an error to error messages array
	 */
	public function addError($msg)
	{
		// Add to error array
		$this->errors[] = $msg;
	}
	
	
	/**
	 *	Add an array of errors all at once
	 */
	public function addErrors(array $msgs)
	{
		foreach($msgs as $msg) {
			$this->addError($msg);
		}
	}
	
	
	/**
	 * Shortcut function to get current adapter's FORMAT_DATE
	 * Should return date only
	 */
	public function getDateFormat()
	{
		return $this->adapter->getDateFormat();
	}
	
	
	/**
	 * Shortcut function to get current adapter's FORMAT_DATETIME
	 * Should return full date and time
	 */
	public function getDateTimeFormat()
	{
		return $this->adapter->getDateTimeFormat();
	}
	
	
	/**
	 * Attempt to load class file based on phpDataMapper naming conventions
	 */
	public static function loadClass($className)
	{
		$loaded = false;
		
		// If class has already been defined, skip loading
		if(class_exists($className, false)) {
			$loaded = true;
		} else {
			// Require phpDataMapper_* files by assumed folder structure (naming convention)
			if(strpos($className, "phpDataMapper") !== false) {
				$classFile = str_replace("_", "/", $className);
				$loaded = require_once(dirname(dirname(__FILE__)) . "/" . $classFile . ".php");
			}
		}
		
		// Ensure required class was loaded
		if(!$loaded) {
			throw new $this->exceptionClass(__METHOD__ . " Failed: Unable to load class '" . $className . "'!");
		}
		
		return $loaded;
	}
	
	
	/**
	 * Prints all executed SQL queries - useful for debugging
	 */
	public function debug($row = null)
	{
		echo "<p>Executed " . $this->getQueryCount() . " queries:</p>";
		echo "<pre>\n";
		print_r(phpDataMapper_Query::$queryLog);
		echo "</pre>\n";
	}
	
	
	/**
	 * Get count of all queries that have been executed
	 * 
	 * @return int
	 */
	public function getQueryCount()
	{
		return count(phpDataMapper_Query::$queryLog);
	}
}


/**
 * Register static 'loadClass' function as an autoloader for files prefixed with 'phpDataMapper_'
 */
spl_autoload_register(array('phpDataMapper_Base', 'loadClass'));