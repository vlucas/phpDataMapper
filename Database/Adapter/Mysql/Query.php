<?php
/**
 * $Id$
 *
 * @package phpDataMapper
 * @author Vance Lucas <vance@vancelucas.com>
 * @link http://phpdatamapper.com
 */
class phpDataMapper_Database_Adapter_Mysql_Query implements phpDataMapper_Database_Query_Interface
{
	protected $select;
	protected $from;
	protected $where = "";
	protected $orderBy;
	protected $groupBy;
	protected $limit;
	protected $limitOffset;
	
	// Actual data to use
	protected $parameters = array();
	
	
	/**
	 * Called from mapper's select() function
	 * 
	 * @param mixed $fields (optional)
	 * @param string $table Table name
	 * @return string
	 */
	public function __construct($fields = "*", $table)
	{
		$this->select = "SELECT " . (is_array($fields) ? implode(', ', $fields) : $fields);
		$this->from($table);
	}
	
	
	/**
	 * From
	 *
	 * @param string $table Name of the table to perform the SELECT query on
	 * @todo Support multiple tables/joins
	 */
	public function from($table = null)
	{
		$this->from = "FROM " . $table;
		return $this;
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
		$sqlWhere = array();
		$binds = array();
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

				// Add to parameters array and add to WHERE clause
				$colParam = str_replace('.', '_', $col) . $ci;
				$sqlWhere[] = $columnSql . " :" . $colParam . "";
				$this->parameters[$colParam] = $value;
			}
			
			// Increment ensures column name distinction
			$ci++;
		}
		
		if(count($sqlWhere) > 0) {
			$this->where .= (empty($this->where) ? "WHERE " : " ".$setType." ") . "(" . implode(' '.$type.' ', $sqlWhere) . ")";
		}
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
				
				$orderBy[] = $field . " " . strtoupper($sort);
			}
		} else {
			$orderBy[] = $fields . " " . $defaultSort;
		}
		$this->orderBy = count($orderBy) > 0 ? "ORDER BY " . implode(', ', $orderBy) : "";
		return $this;
	}
	
	public function groupBy(array $fields = array())
	{
		$groupBy = array();
		foreach($fields as $field) {
			$groupBy[] = $field;
		}
		$this->groupBy = count($groupBy) > 0 ? "GROUP BY " . implode(', ', $groupBy) : "";
		return $this;
	}
	
	
	/**
	 * 
	 */
	public function limit($limit = 20, $offset = null)
	{
		$this->limit = "LIMIT " . (int) $limit;
		if(null !== $offset) {
			$this->limit .= " OFFSET " . (int) $offset;
		}
		return $this;
	}
	
	
	/**
	 * Get array of parameters/binds to execute in a prepared statement
	 */
	public function getParameters()
	{
		return $this->parameters;
	}
	
	
	/**
	 * Get raw SQL code generated from other query builder functions
	 */
	public function sql()
	{
		$sql = $this->select . " \n"
			. $this->from . " \n"
			. $this->where . " \n"
			. $this->groupBy . " \n"
			. $this->orderBy . " \n"
			. $this->limit;
		return $sql;
	}
	
	
	/**
	 * Return Sql code with $this->sql() function
	 */
	public function __toString()
	{
		return $this->sql();
	}
}