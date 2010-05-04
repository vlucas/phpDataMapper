<?php
/**
 * $Id$
 *
 * @package phpDataMapper
 * @link http://phpdatamapper.com
 * @link http://github.com/vlucas/phpDataMapper
 */
class phpDataMapper_Adapter_Mysql extends phpDataMapper_Adapter_PDO
{
	// Format for date columns, formatted for PHP's date() function
	protected $format_date = "Y-m-d";
	protected $format_time = " H:i:s";
	protected $format_datetime = "Y-m-d H:i:s";
	
	// Driver-Specific settings
	protected $_engine = 'InnoDB';
	protected $_charset = 'utf8';
	protected $_collate = 'utf8_unicode_ci';
	
	// Map datamapper field types to actual database adapter types
	// @todo Have to improve this to allow custom types, callbacks, and validation
	protected $_fieldTypeMap = array(
		'string' => array(
			'adapter_type' => 'varchar',
			'length' => 255
			),
		'email' => array(
			'adapter_type' => 'varchar',
			'length' => 255
			),
		'url' => array(
			'adapter_type' => 'varchar',
			'length' => 255
			),
		'tel' => array(
			'adapter_type' => 'varchar',
			'length' => 255
			),
		'password' => array(
			'adapter_type' => 'varchar',
			'length' => 255
			),
		'text' => array('adapter_type' => 'text'),
		'int' => array('adapter_type' => 'int'),
		'integer' => array('adapter_type' => 'int'),
		'bool' => array('adapter_type' => 'tinyint', 'length' => 1),
		'boolean' => array('adapter_type' => 'tinyint', 'length' => 1),
		'float' => array('adapter_type' => 'float'),
		'double' => array('adapter_type' => 'double'),
		'date' => array('adapter_type' => 'date'),
		'datetime' => array('adapter_type' => 'datetime'),
		'time' => array('adapter_type' => 'time')
		);
	
	
	/**
	 * Get DSN string for PDO to connect with
	 * 
	 * @return string
	 */
	public function dsn()
	{
		$dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->database . '';
		return $dsn;
	}
	
	
	/**
	 * Set database engine (InnoDB, MyISAM, etc)
	 */
	public function engine($engine = null)
	{
		if(null !== $engine) {
			$this->_engine = $engine;
		}
		return $this->_engine;
	}
	
	
	/**
	 * Set character set and MySQL collate string
	 */
	public function characterSet($charset, $collate = 'utf8_unicode_ci')
	{
		$this->_charset = $charset;
		$this->_collate = $collate;
	}
	
	
	/**
	 * Get columns for current table
	 *
	 * @param String $table Table name
	 * @return Array
	 */
	protected function getColumnsForTable($table, $source)
	{
		$tableColumns = array();
		$tblCols = $this->connection()->query("SELECT * FROM information_schema.columns WHERE table_schema = '" . $source . "' AND table_name = '" . $table . "'");
		
		if($tblCols) {
			while($columnData = $tblCols->fetch(PDO::FETCH_ASSOC)) {
				$tableColumns[$columnData['COLUMN_NAME']] = $columnData;
			}
			return $tableColumns;
		} else {
			return false;
		}
	}
	
	
	/**
	 * Syntax for each column in CREATE TABLE command
	 *
	 * @param string $fieldName Field name
	 * @param array $fieldInfo Array of field settings
	 * @return string SQL syntax
	 */
	public function migrateSyntaxFieldCreate($fieldName, array $fieldInfo)
	{
		// Ensure field type exists
		if(!isset($this->_fieldTypeMap[$fieldInfo['type']])) {
			throw new phpDataMapper_Exception("Field type '" . $fieldInfo['type'] . "' not supported");
		}
		
		$fieldInfo = array_merge($fieldInfo, $this->_fieldTypeMap[$fieldInfo['type']]);
		
		$syntax = "`" . $fieldName . "` " . $fieldInfo['adapter_type'];
		// Column type and length
		$syntax .= ($fieldInfo['length']) ? '(' . $fieldInfo['length'] . ')' : '';
		// Unsigned
		$syntax .= ($fieldInfo['unsigned']) ? ' unsigned' : '';
		// Collate
		$syntax .= ($fieldInfo['type'] == 'string' || $fieldInfo['type'] == 'text') ? ' COLLATE ' . $this->_collate : '';
		// Nullable
		$isNullable = true;
		if($fieldInfo['required'] || !$fieldInfo['null']) {
			$syntax .= ' NOT NULL';
			$isNullable = false;
		}
		// Default value
		if($fieldInfo['default'] === null && $isNullable) {
			$syntax .= " DEFAULT NULL";
		} elseif($fieldInfo['default'] !== null) {
			$default = $fieldInfo['default'];
			// If it's a boolean and $default is boolean then it should be 1 or 0
			if ( is_bool($default) && $fieldInfo['type'] == "boolean" ) {
				$default = $default ? 1 : 0;
			}
			$syntax .= " DEFAULT '" . $default . "'";
		}
		// Extra
		$syntax .= ($fieldInfo['primary'] && $fieldInfo['serial']) ? ' AUTO_INCREMENT' : '';
		return $syntax;
	}
	
	
	/**
	 * Syntax for CREATE TABLE with given fields and column syntax
	 *
	 * @param string $table Table name
	 * @param array $formattedFields Array of fields with all settings
	 * @param array $columnsSyntax Array of SQL syntax of columns produced by 'migrateSyntaxFieldCreate' function
	 * @return string SQL syntax
	 */
	public function migrateSyntaxTableCreate($table, array $formattedFields, array $columnsSyntax)
	{
		$syntax = "CREATE TABLE IF NOT EXISTS `" . $table . "` (\n";
		// Columns
		$syntax .= implode(",\n", $columnsSyntax);
		
		// Keys...
		$ki = 0;
		$usedKeyNames = array();
		foreach($formattedFields as $fieldName => $fieldInfo) {
			// Determine key field name (can't use same key name twice, so we have to append a number)
			$fieldKeyName = $fieldName;
			while(in_array($fieldKeyName, $usedKeyNames)) {
				$fieldKeyName = $fieldName . '_' . $ki;
			}
			// Key type
			if($fieldInfo['primary']) {
				$syntax .= "\n, PRIMARY KEY(`" . $fieldName . "`)";
			}
			if($fieldInfo['unique']) {
				$syntax .= "\n, UNIQUE KEY `" . $fieldKeyName . "` (`" . $fieldName . "`)";
				$usedKeyNames[] = $fieldKeyName;
			}
			if($fieldInfo['index']) {
				$syntax .= "\n, KEY `" . $fieldKeyName . "` (`" . $fieldName . "`)";
				$usedKeyNames[] = $fieldKeyName;
			}
		}
		
		// Extra
		$syntax .= "\n) ENGINE=" . $this->_engine . " DEFAULT CHARSET=" . $this->_charset . " COLLATE=" . $this->_collate . ";";
		
		return $syntax;
	}
	
	
	/**
	 * Syntax for each column in CREATE TABLE command
	 *
	 * @param string $fieldName Field name
	 * @param array $fieldInfo Array of field settings
	 * @return string SQL syntax
	 */
	public function migrateSyntaxFieldUpdate($fieldName, array $fieldInfo, $add = false)
	{
		return ( $add ? "ADD COLUMN " : "MODIFY " ) . $this->migrateSyntaxFieldCreate($fieldName, $fieldInfo);
	}
	
	
	/**
	 * Syntax for ALTER TABLE with given fields and column syntax
	 *
	 * @param string $table Table name
	 * @param array $formattedFields Array of fields with all settings
	 * @param array $columnsSyntax Array of SQL syntax of columns produced by 'migrateSyntaxFieldUpdate' function
	 * @return string SQL syntax
	 */
	public function migrateSyntaxTableUpdate($table, array $formattedFields, array $columnsSyntax)
	{
		/*
			ALTER TABLE `posts`
			CHANGE `title` `title` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
			CHANGE `status` `status` VARCHAR( 40 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT 'draft'
		*/
		$syntax = "ALTER TABLE `" . $table . "` \n";
		// Columns
		$syntax .= implode(",\n", $columnsSyntax);
		
		
		// Keys...
		$ki = 0;
		$usedKeyNames = array();
		foreach($formattedFields as $fieldName => $fieldInfo) {
			// Determine key field name (can't use same key name twice, so we  have to append a number)
			$fieldKeyName = $fieldName;
			while(in_array($fieldKeyName, $usedKeyNames)) {
				$fieldKeyName = $fieldName . '_' . $ki;
			}
			// Key type
			if($fieldInfo['primary']) {
				$syntax .= ",\n PRIMARY KEY(`" . $fieldName . "`)";
			}
			if($fieldInfo['unique']) {
				$syntax .= ",\n UNIQUE KEY `" . $fieldKeyName . "` (`" . $fieldName . "`)";
				$usedKeyNames[] = $fieldKeyName;
				 // Example: ALTER TABLE `posts` ADD UNIQUE (`url`)
			}
			if($fieldInfo['index']) {
				$syntax .= ",\n KEY `" . $fieldKeyName . "` (`" . $fieldName . "`)";
				$usedKeyNames[] = $fieldKeyName;
			}
		}
		
		// Extra
		$syntax .= ";";
		return $syntax;
	}
}