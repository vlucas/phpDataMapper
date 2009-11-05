<?php
/**
 * Query Object - Used to build adapter-independent queries PHP-style
 * 
 * @package phpDataMapper
 * @author Vance Lucas <vance@vancelucas.com>
 * @link http://phpdatamapper.com
 */
class phpDataMapper_Query implements Countable, IteratorAggregate
{
	protected $adapter;
	
	// Array of all queries that have been executed for any DataMapper (static)
	protected static $queryLog = array();
	
	
	/**
	 *	Constructor Method
	 */
	public function __construct(phpDataMapper_Adapter_Interface $adapter)
	{
		$this->adapter = $adapter;
	}
	
	/**
	 * Called from mapper's select() function
	 * 
	 * @param mixed $fields (optional)
	 * @param string $table Table name
	 * @return string
	 */
	public function select($fields = "*", $table)
	{
		$this->select = "SELECT " . (is_array($fields) ? implode(', ', $fields) : $fields);
		$this->from($table);
	}
	
	
	/**
	 * SPL Countable function
	 * Called automatically when attribute is used in a 'count()' function call
	 *
	 * @return int
	 */
	public function count()
	{
		// Execute query and return count
		$result = $this->execute();
		return ($result !== false) ? count($result) : 0;
	}
	
	
	/**
	 * SPL IteratorAggregate function
	 * Called automatically when attribute is used in a 'foreach' loop
	 *
	 * @return phpDataMapper_Model_ResultSet
	 */
	public function getIterator()
	{
		// Execute query and return ResultSet for iteration
		$result = $this->execute();
		return ($result !== false) ? $result : array();
	}
	
	
	/**
	 * Convenience function passthrough for ResultSet
	 * Triggers execute() and empties current active query
	 *
	 * @return array 
	 */
	public function toArray($keyColumn = null, $valueColumn = null)
	{
		// Execute query and call the 'toArray' function on the ResultSet
		$result = $this->execute();
		return ($result !== false) ? $result->toArray($keyColumn, $valueColumn) : array();
	}
	
	
	/**
	 * Execute and return current active query result set
	 * @param boolean $clearActiveQuery Clears current active query content if true
	 */
	public function execute()
	{
		// Use cached results if found (previous count() or other internal call)
		if($this->activeQueryResults) {
			$results = $this->activeQueryResults;
		} else {
			if($this->activeQuery instanceof phpDataMapper_Query_Interface) {
				$results = $this->query($this->activeQuery->sql(), $this->activeQuery->getParameters());
				$this->activeQueryResults = $results;
			} else {
				$results = array();
			}
		}
		
		return $results;
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
	 * 
	 * @todo Implement extra $clauses array
	 */
	public function all(array $conditions = array())
	{
		// Clear previous active query if found
		if($this->activeQueryResults) {
			$results = $this->clearActiveQuery();
		}
		
		// Build on active query if it has not been executed yet
		if($this->activeQuery instanceof phpDataMapper_Database_Query_Interface) {
			$this->activeQuery->where($conditions)->orderBy($orderBy);
		} else {
			// New active query
			$this->activeQuery = $this->select()->where($conditions)->orderBy($orderBy);
		}
		return $this;
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
			return new $adapterClass($fields, $this->table);
		} else {
			throw new $this->exceptionClass(__METHOD__ . " Error: Unable to load new query builder for adapter: '" . $adapterName . "'");
		}
	}
	
	
	/**
	 * Limit executed query to specified amount of rows
	 * Implemented at adapter-level for databases that support it
	 * 
	 * @param int $limit Number of records to return
	 * @param int $offset Row to start at for limited result set
	 *
	 * @todo Implement limit functionality for database adapters that do not support any kind of LIMIT clause
	 */
	public function limit($limit = 20, $offset = null)
	{
		if($this->activeQuery instanceof phpDataMapper_Query_Interface) {
			$this->adapterQuery->limit($limit, $offset);
		}
		return $this;
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
	 * Log query
	 *
	 * @param string $sql
	 * @param array $data
	 */
	public static function logQuery($sql, $data = null)
	{
		self::$queryLog[] = array(
			'query' => $sql,
			'data' => $data
			);
	}
}