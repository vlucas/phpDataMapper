<?php
/**
 * Base DataMapper Model
 * 
 * @package phpDataMapper
 * @link http://phpdatamapper.com
 * @link http://github.com/vlucas/phpDataMapper
 */
class phpDataMapper_Base
{
	// Class Names for required classes - Here so they can be easily overridden
	protected $_entityClass = 'phpDataMapper_Entity';
	protected $_queryClass = 'phpDataMapper_Query';
	protected $_collectionClass = 'phpDataMapper_Collection';
	protected $_exceptionClass = 'phpDataMapper_Exception';
	
	// Stored adapter connections
	protected $_adapter;
	protected $_adapterRead;
	
	// Array of error messages and types
	protected $_errors = array();
	
	// Query log
	protected static $_queryLog = array();
	
	// Store cached field info
	protected $_fields = array();
	protected $_relations = array();
	protected $_primaryKey;
	
	// Data source setup info
	protected $source;
	/**
	=== EXAMPLE fields ===
	
	public $id = array('type' => 'int', 'primary' => true);
	public $name = array('type' => 'string', 'required' => true);
	public $date_created = array('type' => 'datetime');
	
	=== EXAMPLE Relationship associations ===
	
	public $comments = array(
		'type' => 'relation',
		'relation' => 'HasMany',
		'mapper' => 'CommentsModel',
		'where' => array('self.id' => 'foreign.comment_id'),
		);
	
	======================
	*/
	
	
	/**
	 *	Constructor Method
	 */
	public function __construct(phpDataMapper_Adapter_Interface $adapter, $adapterRead = null)
	{
		$this->_adapter = $adapter;
		
		// Ensure required classes for minimum activity are loaded
		$this->loadClass($this->_entityClass);
		$this->loadClass($this->_queryClass);
		$this->loadClass($this->_collectionClass);
		$this->loadClass($this->_exceptionClass);
		
		// Slave adapter if given (usually for reads)
		if(null !== $adapterRead) {
			if($adapterRead instanceof phpDataMapper_Adapter_Interface) {
				$this->_adapterRead = $adapterRead;
			} else {
				throw new InvalidArgumentException("Secondary/Slave adapter must implement 'phpDataMapper_Adapter_Interface'");
			}
		}
		
		// Ensure table has been defined
		if(!$this->source) {
			throw new $this->_exceptionClass("Error: Source name must be defined - please define the \$source variable. This can be a database table name, a file name, or a URL, depending on your adapter.");
		}
		
		// Ensure fields have been defined for current table
		if(!$this->fields()) {
			throw new $this->_exceptionClass("Error: Fields must be defined");
		}
	}
	
	
	/**
	 * Get current adapter object
	 */
	public function adapter()
	{
		return $this->_adapter;
	}
	
	
	/**
	 * Get adapter object that will serve as the 'slave' for reads
	 */
	public function adapterRead()
	{
		if($this->_adapterRead) {
			return $this->_adapterRead;
		} else {
			return $this->_adapter;
		}
	}
	
	
	/**
	 * Get entity class name to use
	 * 
	 * @return string
	 */
	public function entityClass()
	{
		return $this->_entityClass;
	}
	
	
	/**
	 * Get query class name to use
	 * 
	 * @return string
	 */
	public function queryClass()
	{
		return $this->_queryClass;
	}
	
	
	/**
	 * Get collection class name to use
	 * 
	 * @return string
	 */
	public function collectionClass()
	{
		return $this->_collectionClass;
	}
	
	
	/**
	 * Get name of the data source
	 */
	public function source()
	{
		return $this->source;
	}
	
	
	/**
	 * Get formatted fields with all neccesary array keys and values.
	 * Merges defaults with defined field values to ensure all options exist for each field.
	 *
	 * @return array Defined fields plus all defaults for full array of all possible options
	 */
	public function fields()
	{
		if($this->_fields) {
			$returnFields = $this->_fields;
		} else {
			$getFields = create_function('$obj', 'return get_object_vars($obj);');
			$fields = $getFields($this);
			
			// Default settings for all fields
			$fieldDefaults = array(
				'type' => 'string',
				'default' => null,
				'length' => null,
				'required' => false,
				'null' => true,
				'unsigned' => false,
				
				'auto_increment' => false,
				'primary' => false,
				'index' => false,
				'unique' => false,
				
				'relation' => false
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
			
			$returnFields = array();
			foreach($fields as $fieldName => $fieldOpts) {
				// Format field will full set of default options
				if(isset($fieldInfo['type']) && isset($fieldTypeDefaults[$fieldOpts['type']])) {
					// Include type defaults
					$fieldOpts = array_merge($fieldDefaults, $fieldTypeDefaults[$fieldOpts['type']], $fieldOpts);
				} else {
					// Merge with defaults
					$fieldOpts = array_merge($fieldDefaults, $fieldOpts);
				}
				
				// Store primary key
				if($fieldOpts['primary'] === true) {
					$this->_primaryKey = $fieldName;
				}
				// Store relations (and remove them from the mix of regular fields)
				if($fieldOpts['type'] == 'relation') {
					$this->_relations[$fieldName] = $fieldOpts;
					continue; // skip, not a field
				}
				
				$returnFields[$fieldName] = $fieldOpts;
			}
			$this->_fields = $returnFields;
		}
		return $returnFields;
	}
	
	
	/**
	 * Get defined relations
	 */
	public function relations()
	{
		if(!$this->_relations) {
			$this->fields();
		}
		return $this->_relations;
	}
	
	
	/**
	 * Get value of primary key for given row result
	 */
	public function primaryKey(phpDataMapper_Entity $entity)
	{
		$pkField = $this->primaryKeyField();
		return $entity->$pkField;
	}
	
	
	/**
	 * Get value of primary key for given row result
	 */
	public function primaryKeyField()
	{
		return $this->_primaryKey;
	}
	
	
	/**
	 * Check if field exists in defined fields
	 */
	public function fieldExists($field)
	{
		return array_key_exists($field, $this->fields());
	}
	
	
	/**
	 * Load record from primary key
	 */
	public function get($primaryKeyValue = 0)
	{
		// Create new row object
		if(!$primaryKeyValue) {
			$entity = new $this->_entityClass();
		
		// Find record by primary key
		} else {
			$entity = $this->first(array($this->primaryKeyField() => $primaryKeyValue));
		}
		return $entity;
	}
	
	
	/**
	 * Load defined relations 
	 */
	public function getRelationsFor(phpDataMapper_Entity $entity)
	{
		$relatedColumns = array();
		if(count($this->relations()) > 0) {
			foreach($this->relations() as $column => $relation) {
				$mapperName = isset($relation['mapper']) ? $relation['mapper'] : false;
				if(!$mapperName) {
					throw new $this->_exceptionClass("Relationship mapper for '" . $column . "' has not been defined.");
				}
				
				// Load foreign keys with data from current row
				// Replace 'entity.[col]' with the column value from the passed entity object
				$relConditions = array();
				if(isset($relation['where'])) {
					foreach($relation['where'] as $relationCol => $col) {
						if(strpos('entity.', $col) == 0) {
							$col = str_replace('entity.', '', $col);
						}
						$relConditions[$relationCol] = $entity->$col;
					}
				}
				
				// Create new instance of mapper
				$mapper = new $mapperName($this->adapter());
				
				// Load relation class
				$relationClass = 'phpDataMapper_Relation_' . $relation['relation'];
				if($loadedRel = $this->loadClass($relationClass)) {
					// Set column equal to relation class instance
					$relationObj = new $relationClass($mapper, $relConditions, $relation);
					$relatedColumns[$column] = $relationObj;
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
			$stmt->setFetchMode(PDO::FETCH_CLASS, $this->entityClass, array());
			
			// Fetch all results into new DataMapper_Result class
			while($entity = $stmt->fetch(PDO::FETCH_CLASS)) {
				
				// Load relations for this row
				$relations = $this->getRelationsFor($entity);
				if($relations && is_array($relations) && count($relations) > 0) {
					foreach($relations as $relationCol => $relationObj) {
						$entity->$relationCol = $relationObj;
					}
				}
				
				// Store in array for ResultSet
				$results[] = $entity;
				
				// Store primary key of each unique record in set
				$pk = $this->primaryKey($entity);
				if(!in_array($pk, $resultsIdentities) && !empty($pk)) {
					$resultsIdentities[] = $pk;
				}
				
				// Mark row as loaded
				$entity->loaded(true);
			}
			// Ensure set is closed
			$stmt->closeCursor();
			
			return new $this->_collectionClass($results, $resultsIdentities);
			
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
	 */
	public function all(array $conditions = array())
	{
		return $this->select()->where($conditions);
	}
	
	
	/**
	 * Find first record matching given conditions
	 *
	 * @param array $conditions Array of conditions in column => value pairs
	 */
	public function first(array $conditions = array())
	{
		$query = $this->select()->where($conditions)->limit(1);
		$entitys = $this->adapterRead()->read($query);
		if($entitys) {
			return $entitys->first();
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
		if($stmt = $this->adapter()->prepare($sql)) {
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
		$query = new $this->_queryClass($this);
		$query->select($fields, $this->source());
		return $query;
	}
	
	
	/**
	 * Save related rows of data
	 */
	protected function saveRelatedRowsFor($entity, array $fillData = array())
	{
		$relationColumns = $this->getRelationsFor($entity);
		foreach($entity->toArray() as $field => $value) {
			if($relationColumns && array_key_exists($field, $relationColumns) && (is_array($value) || is_object($value))) {
				foreach($value as $relatedRow) {
					// Determine relation object
					if($value instanceof phpDataMapper_Relation) {
						$relatedObj = $value;
					} else {
						$relatedObj = $relationColumns[$field];
					}
					$relatedMapper = $relatedObj->mapper();
					
					// Row object
					if($relatedRow instanceof phpDataMapper_Entity) {
						$relatedRowObj = $relatedRow;
						
					// Associative array
					} elseif(is_array($relatedRow)) {
						$relatedRowObj = new $this->entityClass($relatedRow);
					}
					
					// Set column values on row only if other data has been updated (prevents queries for unchanged existing rows)
					if(count($relatedRowObj->dataModified()) > 0) {
						$fillData = array_merge($relatedObj->foreignKeys(), $fillData);
						$relatedRowObj->data($fillData);
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
	public function save(phpDataMapper_Entity $entity)
	{
		// Run validation
		if($this->validate($entity)) {
			$pk = $this->primaryKey($entity);
			// No primary key, insert
			if(empty($pk)) {
				$result = $this->insert($entity);
			// Has primary key, update
			} else {
				$result = $this->update($entity);
			}
		} else {
			$result = false;
		}
		
		return $result;
	}
	
	
	/**
	 * Insert given row object with set properties
	 */
	public function insert(phpDataMapper_Entity $entity)
	{
		$data = array();
		$entityData = $entity->toArray();
		foreach($entityData as $field => $value) {
			if($this->fieldExists($field)) {
				// Empty values will be NULL (easier to be handled by databases)
				$data[$field] = $this->isEmpty($value) ? null : $value;
			}
		}
		
		// Ensure there is actually data to update
		if(count($data) > 0) {
			$result = $this->adapter()->create($this->source(), $data);
			// Update primary key on row
			$pkField = $this->primaryKeyField();
			$entity->$pkField = $result;
		} else {
			$result = false;
		}
		
		// Save related rows
		if($result) {
			$this->saveRelatedRowsFor($entity);
		}
		
		return $result;
	}
	
	
	/**
	 * Update given row object
	 */
	public function update(phpDataMapper_Entity $entity)
	{
		// Ensure fields exist to prevent errors
		$binds = array();
		foreach($entity->dataModified() as $field => $value) {
			if($this->fieldExists($field)) {
				// Empty values will be NULL (easier to be handled by databases)
				$binds[$field] = $this->isEmpty($value) ? null : $value;
			}
		}
		
		// Handle with adapter
		$result = $this->adapter()->update($this->getSourceName(), $binds, array($this->primaryKeyField() => $this->primaryKey($entity)));
		
		// Save related rows
		if($result) {
			$this->saveRelatedRowsFor($entity);
		}
		
		return $result;
	}
	
	
	/**
	 * Delete items matching given conditions
	 *
	 * @param mixed $conditions Array of conditions in column => value pairs or Entity object
	 */
	public function delete($conditions)
	{
		if($conditions instanceof phpDataMapper_Entity) {
			$conditions = array($this->primaryKeyField() => $this->primaryKey($conditions));
		}
		
		if(is_array($conditions)) {
			return $this->adapter()->delete($this->source(), $conditions);
		} else {
			throw new $this->_exceptionClass("Delete conditions must be entity object or array");
		}
	}
	
	
	/**
	 * Truncate a database table
	 * Should delete all rows and reset serial/auto_increment keys to 0
	 */
	public function truncateTable() {
		return $this->adapter()->truncateTable($this->source());
	}
	
	
	/**
	 * Drop a database table
	 * Destructive and dangerous - drops entire table and all data
	 */
	public function dropTable() {
		return $this->adapter()->dropTable($this->source());
	}
	
	
	/**
	 * Run set validation rules on fields
	 * 
	 * @todo A LOT more to do here... More validation, break up into classes with rules, etc.
	 */
	public function validate(phpDataMapper_Entity $entity)
	{
		// Check validation rules on each feild
		foreach($this->fields() as $field => $fieldAttrs) {
			if(isset($fieldAttrs['required']) && true === $fieldAttrs['required']) {
				// Required field
				if(empty($entity->$field)) {
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
		return $this->adapter()->migrate($this->source(), $this->fields());
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
			throw new Exception(__METHOD__ . " Failed: Unable to load class '" . $className . "'!");
		}
		
		return $loaded;
	}
	
	
	/**
	 * Prints all executed SQL queries - useful for debugging
	 */
	public function debug($entity = null)
	{
		echo "<p>Executed " . $this->queryCount() . " queries:</p>";
		echo "<pre>\n";
		print_r(self::$_queryLog);
		echo "</pre>\n";
	}
	
	
	/**
	 * Get count of all queries that have been executed
	 * 
	 * @return int
	 */
	public function queryCount()
	{
		return count(self::$_queryLog);
	}
	
	
	/**
	 * Log query
	 *
	 * @param string $sql
	 * @param array $data
	 */
	public static function logQuery($sql, $data = null)
	{
		self::$_queryLog[] = array(
			'query' => $sql,
			'data' => $data
			);
	}
}


/**
 * Register static 'loadClass' function as an autoloader for files prefixed with 'phpDataMapper_'
 */
spl_autoload_register(array('phpDataMapper_Base', 'loadClass'));