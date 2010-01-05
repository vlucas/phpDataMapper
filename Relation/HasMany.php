<?php
require_once(dirname(dirname(__FILE__)) . '/Relation.php');
/**
 * DataMapper class for 'has many' relations
 * 
 * @package phpDataMapper
 * @author Vance Lucas <vance@vancelucas.com>
 * @link http://phpdatamapper.com
 */
class phpDataMapper_Relation_HasMany extends phpDataMapper_Relation implements Countable, IteratorAggregate, ArrayAccess
{
	/**
	 * Load records with current relation data
	 */
	public function all()
	{
		return $this->mapper()->all($this->foreignKeys(), $this->getRelationSorting());
	}
	
	
	/**
	 * Find first record in the set
	 */
	public function first()
	{
		return $this->mapper()->first($this->foreignKeys(), $this->getRelationSorting());
	}
	
	
	/**
	 * SPL Countable function
	 * Called automatically when attribute is used in a 'count()' function call
	 *
	 * @return int
	 */
	public function count()
	{
		// Load related records for current row
		return count($this->findAllRelation());
	}
	
	
	/**
	 * SPL IteratorAggregate function
	 * Called automatically when attribute is used in a 'foreach' loop
	 *
	 * @return phpDataMapper_Model_ResultSet
	 */
	public function getIterator()
	{
		// Load related records for current row
		$data = $this->findAllRelation();
		return $data ? $data : array();
	}
	
	
	// SPL - ArrayAccess functions
	// ----------------------------------------------
	public function offsetExists($key) {
		$this->findAllRelation();
		return isset($this->relationRows[$key]);
	}
	
	public function offsetGet($key) {
		$this->findAllRelation();
		return $this->relationRows[$key];
	}
	
	public function offsetSet($key, $value) {
		$this->findAllRelation();
		
		if($key === null) {
			return $this->relationRows[] = $value;
		} else {
			return $this->relationRows[$key] = $value;
		}
	}
	
	public function offsetUnset($key) {
		$this->findAllRelation();
		unset($this->relationRows[$key]);
	}
	// ----------------------------------------------
}