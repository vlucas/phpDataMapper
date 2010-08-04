<?php
/**
 * DataMapper entity class - each item is fetched into this object
 * 
 * @package phpDataMapper
 * @link http://phpdatamapper.com
 * @link http://github.com/vlucas/phpDataMapper
 */
class phpDataMapper_Entity
{
	protected $_loaded;
	protected $_data = array();
	protected $_dataModified = array();
	protected $_getterIgnore = array();
	protected $_setterIgnore = array();
	
	
	/**
	 * Constructor function
	 */
	public function __construct($data = null)
	{
		// Set given data
		if($data !== null) {
			$this->data($data);
		}
		
		// Mark record as loaded
		$this->loaded(true);
	}
	
	
	/**
	 * Mark row as 'loaded'
	 * Any data set after row is loaded will be modified data
	 *
	 * @param boolean $loaded
	 */
	public function loaded($loaded)
	{
		$this->_loaded = (bool) $loaded;
	}
	
	
	/**
	 *	Sets an object or array
	 */
	public function data($data = null)
	{
		if(null !== $data) {
			if(is_object($data) || is_array($data)) {
				foreach($data as $k => $v) {
					$this->$k = $v;
				}
				return $this;
			} else {
				throw new InvalidArgumentException(__METHOD__ . " Expected array or object input - " . gettype($data) . " given");
			}
		} else {
			return $this->toArray();
		}
	}
	
	
	/**
	 * Returns array of key => value pairs for row data
	 * 
	 * @return array
	 */
	public function dataModified()
	{
		return $this->_dataModified;
	}
	
	
	/**
	 * Returns array of key => value pairs for row data
	 * 
	 * @return array
	 */
	public function toArray()
	{
		return array_merge($this->_data, $this->_dataModified);
	}
	
	
	/**
	 * Return JSON-encoded row (convenience function)
	 * Only works for basic objects right now
	 * 
	 * @todo Return fully mapped row objects with related rows (has one, has many, etc)
	 */
	public function toJson()
	{
		return json_encode($this->data());
	}
	
	
	/**
	 * Enable isset() for object properties
	 */
	public function __isset($key)
	{
		return ($this->$key !== null) ? true : false;
	}
	
	
	/**
	 * Getter
	 */
	public function __get($var)
	{
		// Check for custom getter method (override)
		$getMethod = 'get_' . $var;
		if(method_exists($this, $getMethod) && !array_key_exists($var, $this->_getterIgnore)) {
			$this->_getterIgnore[$var] = 1; // Tell this function to ignore the overload on further calls for this variable
			$result = $this->$getMethod(); // Call custom getter
			unset($this->_getterIgnore[$var]); // Remove ignore rule
			return $result;
		
		// Handle default way
		} else {
			if(isset($this->_dataModified[$var])) {
				return $this->_dataModified[$var];
			} elseif(isset($this->_data[$var])) {
				return $this->_data[$var];
			} else {
				return null;
			}
		}
	}
	
	
	/**
	 * Setter
	 */
	public function __set($var, $value)
	{
		// Check for custom setter method (override)
		$setMethod = 'set_' . $var;
		if(method_exists($this, $setMethod) && !array_key_exists($var, $this->_setterIgnore)) {
			$this->_setterIgnore[$var] = 1; // Tell this function to ignore the overload on further calls for this variable
			$result = $this->$setMethod($value); // Call custom setter
			unset($this->_setterIgnore[$var]); // Remove ignore rule
			return $result;
		
		// Handle default way
		} else {
			if($this->_loaded) {
				$this->_dataModified[$var] = $value;
			} else {
				$this->_data[$var] = $value;
			}
		}
	}
}