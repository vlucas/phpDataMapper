<?php
/**
 * $Id$
 *
 * @package phpDataMapper
 * @author Vance Lucas <vance@vancelucas.com>
 * @link http://phpdatamapper.com
 */
interface phpDataMapper_Database_Query_Interface
{	
	/**
	 * Called from mapper's select() function
	 * 
	 * @param mixed $fields (optional)
	 * @param string $table Table name
	 * @return string
	 */
	public function __construct($fields = "*", $table);
	
	/**
	 * From
	 *
	 * @param string $table Name of the table to perform the SELECT query on
	 * @todo Support multiple tables/joins
	 */
	public function from($table = null);
	
	/**
	 * WHERE conditions
	 *
	 * @param array $conditions Array of conditions for this clause
	 * @param string $type Keyword that will separate each condition - "AND", "OR"
	 * @param string $setType Keyword that will separate the whole set of conditions - "AND", "OR"
	 */
	public function where(array $conditions = array(), $type = "AND", $setType = "AND");
	public function orWhere(array $conditions = array(), $type = "AND");
	public function andWhere(array $conditions = array(), $type = "AND");
	
	/**
	 * ORDER BY columns
	 */
	public function orderBy($fields = array());
	
	/**
	 * GROUP BY columns
	 */
	public function groupBy(array $fields = array());
	
	/**
	 * LIMIT query or result set
	 */
	public function limit($limit = 20, $offset = null);
	
	/**
	 * Get array of parameters/binds to execute in a prepared statement
	 */
	public function getParameters();
	
	/**
	 * Get raw SQL code generated from other query builder functions
	 */
	public function sql();
	
	/**
	 * Return Sql code with $this->sql() function
	 */
	public function __toString();
}