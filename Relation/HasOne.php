<?php
require_once(dirname(dirname(__FILE__)) . '/Relation.php');
/**
 * $Id$
 * 
 * DataMapper class for 'has one' relations
 * 
 * @package phpDataMapper
 * @author Vance Lucas <vance@vancelucas.com>
 * @link http://phpdatamapper.com
 * 
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 */
class phpDataMapper_Model_Relation_HasOne extends phpDataMapper_Model_Relation
{
	/**
	 * Load records with current relation data
	 */
	public function all()
	{
		return $this->mapper->first($this->getForeignKeys(), $this->getRelationSorting());
	}
	
	
	/**
	 * isset() functionality passthrough to row
	 */
	public function __isset($key)
	{
		$row = $this->findAllRelation();
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
		$row = $this->findAllRelation();
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
		$row = $this->findAllRelation();
		if($row) {
			$row->$var = $value;
		}
	}
	
	
	/**
	 * Passthrough for method calls on row
	 */
	public function __call($func, $args)
	{
		$row = $this->findAllRelation();
		if($row) {
			return call_user_func_array(array($row, $func), $args);
		} else {
			return false;
		}
	}
}