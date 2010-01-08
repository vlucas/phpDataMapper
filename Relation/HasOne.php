<?php
require_once(dirname(dirname(__FILE__)) . '/Relation.php');
/**
 * DataMapper class for 'has one' relations
 * 
 * @package phpDataMapper
 * @author Vance Lucas <vance@vancelucas.com>
 * @link http://phpdatamapper.com
 */
class phpDataMapper_Relation_HasOne extends phpDataMapper_Relation
{
	/**
	 * Load records with current relation data
	 */
	public function all()
	{
		return $this->mapper()->all($this->conditions())->order($this->relationOrder())->first();
	}
	
	
	/**
	 * isset() functionality passthrough to row
	 */
	public function __isset($key)
	{
		$row = $this->execute();
		if($row) {
			return isset($row->$key);
		} else {
			return false;
		}
	}
	
	
	/**
	 * Getter passthrough to row
	 */
	public function __get($var)
	{
		$row = $this->execute();
		if($row) {
			return $row->$var;
		} else {
			return null;
		}
	}
	
	
	/**
	 * Setter passthrough to row
	 */
	public function __set($var, $value)
	{
		$row = $this->execute();
		if($row) {
			$row->$var = $value;
		}
	}
	
	
	/**
	 * Passthrough for method calls on row
	 */
	public function __call($func, $args)
	{
		$row = $this->execute();
		if($row) {
			return call_user_func_array(array($row, $func), $args);
		} else {
			return false;
		}
	}
}