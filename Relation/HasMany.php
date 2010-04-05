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
	 *
	 * @return phpDataMapper_Query
	 */
	public function all()
	{
		return $this->mapper()->all($this->conditions())->order($this->relationOrder());
	}
	
	
	/**
	 * Find first record in the set
	 *
	 * @return phpDataMapper_Entity
	 */
	public function first()
	{
		return $this->all()->first();
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
		return count($this->execute());
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
		$data = $this->execute();
		return $data ? $data : array();
	}
	
	
	/**
	 * Passthrough for method chaining on the Query object
	 */
	public function __call($func, $args)
	{
		return call_user_func_array(array($this->execute(), $func), $args);
	}
	
	
	// SPL - ArrayAccess functions
	// ----------------------------------------------
	public function offsetExists($key) {
		$this->execute();
		return isset($this->_collection[$key]);
	}
	
	public function offsetGet($key) {
		$this->execute();
		return $this->_collection[$key];
	}
	
	public function offsetSet($key, $value) {
		$this->execute();
		
		if($key === null) {
			return $this->_collection[] = $value;
		} else {
			return $this->_collection[$key] = $value;
		}
	}
	
	public function offsetUnset($key) {
		$this->execute();
		unset($this->_collection[$key]);
	}
	// ----------------------------------------------
}