<?php
/**
 * DataMapper abstract class for relations
 * 
 * @package phpDataMapper
 * @author Vance Lucas <vance@vancelucas.com>
 * @link http://phpdatamapper.com
 */
abstract class phpDataMapper_Relation
{
	protected $_mapper;
	protected $_foreignKeys;
	protected $_conditions;
	protected $_relationData;
	protected $_collection;
	protected $_relationRowCount;
	
	
	/**
	 * Constructor function
	 *
	 * @param object $mapper DataMapper object to query on for relationship data
	 * @param array $resultsIdentities Array of key values for given result set primary key
	 */
	public function __construct(phpDataMapper_Base $mapper, array $conditions, array $relationData)
	{
		$this->_mapper = $mapper;
		$this->_conditions = $conditions;
		$this->_relationData = $relationData;
	}
	
	
	/**
	 * Get related DataMapper object
	 */
	public function mapper()
	{
		return $this->_mapper;
	}
	
	
	/**
	 * Get foreign key relations
	 *
	 * @return array
	 */
	public function conditions()
	{
		return $this->_conditions;
	}
	
	
	/**
	 * Get sorting for relations
	 *
	 * @return array
	 */
	public function relationOrder()
	{
		$sorting = isset($this->_relationData['order']) ? $this->_relationData['order'] : array();
		return $sorting;
	}
	
	
	/**
	 * Called automatically when attribute is printed
	 */
	public function __toString()
	{
		// Load related records for current row
		$success = $this->findAllRelation();
		return ($success) ? "1" : "0";
	}
	
	
	
	/**
	 * Select all related records
	 */
	abstract public function all();
	
	
	/**
	 * Internal function, caches fetched related rows from all() function call
	 */
	protected function execute()
	{
		if(!$this->_collection) {
			$this->_collection = $this->all();
		}
		return $this->_collection;
	}
}