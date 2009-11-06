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
	protected $mapper;
	
	// Storage for query properties
	public $fields = array();
	public $sourceName;
	public $conditions = array();
	public $orderBy = array();
	public $groupBy = array();
	public $limit;
	public $limitOffset;
	
	
	/**
	 *	Constructor Method
	 */
	public function __construct(phpDataMapper_Base $mapper)
	{
		$this->mapper = $mapper;
	}
	
	
	/**
	 * Called from mapper's select() function
	 * 
	 * @param mixed $fields (optional)
	 * @param string $sourceName Data source name
	 * @return string
	 */
	public function select($fields = "*", $sourceName = null)
	{
		$this->fields = (is_string($fields) ? explode(',', $fields) : $fields);
		$this->from($sourceName);
	}
	
	
	/**
	 * From
	 *
	 * @param string $sourceName Name of the data source to perform a query on
	 * @todo Support multiple sources/joins
	 */
	public function from($sourceName = null)
	{
		$this->sourceName = $sourceName;
		return $this;
	}
	
	
	/**
	 * Find records with given conditions
	 * If all parameters are empty, find all records
	 *
	 * @param array $conditions Array of conditions in column => value pairs
	 */
	public function all(array $conditions = array())
	{
		return $this->where($conditions);
	}
	
	
	/**
	 * WHERE conditions
	 * 
	 * @param array $conditions Array of conditions for this clause
	 * @param string $type Keyword that will separate each condition - "AND", "OR"
	 * @param string $setType Keyword that will separate the whole set of conditions - "AND", "OR"
	 */
	public function where(array $conditions = array(), $type = "AND", $setType = "AND")
	{
		$where = array();
		$where['conditions'] = $conditions;
		$where['type'] = $type;
		$where['setType'] = $setType;
		
		$this->conditions[] = $where;
		return $this;
	}
	public function orWhere(array $conditions = array(), $type = "AND")
	{
		return $this->where($conditions, $type, "OR");
	}
	public function andWhere(array $conditions = array(), $type = "AND")
	{
		return $this->where($conditions, $type, "AND");
	}
	
	
	/**
	 * ORDER BY columns
	 *
	 * @param array $fields Array of field names to use for sorting
	 * @return $this
	 */
	public function orderBy($fields = array())
	{
		$orderBy = array();
		$defaultSort = "ASC";
		if(is_array($fields)) {
			foreach($fields as $field => $sort) {
				// Numeric index - field as array entry, not key/value pair
				if(is_numeric($field)) {
					$field = $sort;
					$sort = $defaultSort;
				}
				
				$this->orderBy[$field] = strtoupper($sort);
			}
		} else {
			$this->orderBy[$fields] = $defaultSort;
		}
		return $this;
	}
	
	
	/**
	 * GROUP BY clause
	 *
	 * @param array $fields Array of field names to use for grouping
	 * @return $this
	 */
	public function groupBy(array $fields = array())
	{
		$groupBy = array();
		foreach($fields as $field) {
			$this->groupBy[] = $field;
		}
		return $this;
	}
	
	
	/**
	 * Limit executed query to specified amount of rows
	 * Implemented at adapter-level for databases that support it
	 * 
	 * @param int $limit Number of records to return
	 * @param int $offset Row to start at for limited result set
	 */
	public function limit($limit = 20, $offset = null)
	{
		$this->limit = $limit;
		$this->limitOffset = $offset;
		return $this;
	}
	
	
	
	
	
	// ===================================================================
	
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
	 * @return phpDataMapper_Query_Set
	 */
	public function getIterator()
	{
		// Execute query and return result set for iteration
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
		// Build SQL
		// Prepared Statement
		// Return ResultSet
		return $this->mapper->getAdapter()->read($this);
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