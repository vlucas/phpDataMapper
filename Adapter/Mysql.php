<?php
require_once(dirname(__FILE__) . '/Abstract.php');
/**
 * $Id$
 *
 * @package phpDataMapper
 * @author Vance Lucas <vance@vancelucas.com>
 * @link http://phpdatamapper.com
 */
class phpDataMapper_Adapter_Mysql extends phpDataMapper_Adapter_Abstract
{
	// Format for date columns, formatted for PHP's date() function
	protected $format_date = "Y-m-d";
	protected $format_datetime = "Y-m-d H:i:s";
	
	// Driver-Specific settings
	protected $engine = 'InnoDB';
	protected $charset = 'utf8';
	protected $collate = 'utf8_unicode_ci';
	// Map datamapper field types to actual database adapter types
	protected $fieldTypeMap = array(
		'string' => 'varchar',
		'text' => 'text',
		'int' => 'int',
		'integer' => 'int',
		'bool' => 'tinyint',
		'boolean' => 'tinyint',
		'float' => 'float',
		'double' => 'double',
		'date' => 'date',
		'datetime' => 'datetime'
		);
	
	
	/**
	 * Get DSN string for PDO to connect with
	 * 
	 * @return string
	 */
	public function getDsn()
	{
		$dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->database . '';
		return $dsn;
	}
	
	
	/**
	 * Set database engine (InnoDB, MyISAM *shudder*, etc)
	 */
	public function setEngine($engine)
	{
		$this->engine = $engine;
	}
	
	
	/**
	 * Set character set and MySQL collate string
	 */
	public function setCharacterSet($charset, $collate = 'utf8_unicode_ci')
	{
		$this->charset = $charset;
		$this->collate = $collate;
	}
	
	
	/**
	 * Get columns for current table
	 *
	 * @param String $table Table name
	 * @return Array
	 */
	protected function getColumnsForTable($table)
	{
		$tableColumns = array();
		$tblCols = $this->getConnection()->query("SELECT * FROM information_schema.columns WHERE table_name = '" . $table . "'");
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
	 * Column syntax conversion
	 */
	 /*
	public function migrateColumnSyntax($column, $colData)
	{
		// NULL values allowed?
		if(strtolower($colData['Null']) == "no") {
			$field['required'] = true; ((strlen($colData['Default']) == 0) ? " DEFAULT NULL" : "") ;
			// Default value
			if(strlen($colData['Default']) > 0) {
				$field['default'] = $colData['Default'];
			}
		}
		
		
		
		$default = (strlen($colData['Default']) == 0) ? "" : " DEFAULT '" . $colData['Default'] . "'";
		$extra = empty($colData['Extra']) ? "" : " " . $colData['Extra'] ;
	}
	*?
	
	
	/**
	 * Column syntax
	 */
	 /*
	public function migrateColumnSyntaxOld($column, $colData, $creating = false)
	{
		$null = (strtolower($colData['Null']) == "no") ? " NOT NULL" : " NULL" . ((strlen($colData['Default']) == 0) ? " DEFAULT NULL" : "") ;
		$default = (strlen($colData['Default']) == 0) ? "" : " DEFAULT '" . $colData['Default'] . "'";
		$extra = empty($colData['Extra']) ? "" : " " . $colData['Extra'] ;
		
		// Determine if we can specify a character set
		$charset = '';
		$charsetTypes = array('CHAR', 'TEXT', 'ENUM', 'SET');
		foreach($charsetTypes as $colType) {
			if(strpos(strtoupper($colData['Type']), $colType) !== false) {
				$charset = " COLLATE utf8_unicode_ci";
			}
		}
		
		// Add key if the column has one
		$key = '';
		if(isset($colData['Key'])) {
			if($colData['Key'] == "PRI") {
				$key = ", " . ($creating ? '' : 'ADD') . " PRIMARY KEY(" . $column . ")";
			}
			if($colData['Key'] == "MUL") {
				$key = ", " . ($creating ? '' : 'ADD') . " INDEX(" . $column . ")";
			}
			if($colData['Key'] == "UNI") {
				$key = ", " . ($creating ? '' : 'ADD') . " UNIQUE(" . $column . ")";
			}
		}
		
		$sql = "`" . $column . "` " . $colData['Type'] . " " . $charset . $default . $null . $extra . $key;
		return $sql;
	}
	*/
	
	
	/**
	 * Syntax for each column in CREATE TABLE command
	 *
	 * @param string $fieldName Field name
	 * @param array $fieldInfo Array of field settings
	 * @return string SQL syntax
	 */
	public function migrateSyntaxFieldCreate($fieldName, array $fieldInfo)
	{
		$syntax = "`" . $fieldName . "` " . $fieldInfo['adapter_type'];
		// Column type and length
		$syntax .= ($fieldInfo['length']) ? '(' . $fieldInfo['length'] . ')' : '';
		// Unsigned
		$syntax .= ($fieldInfo['unsigned']) ? ' unsigned' : '';
		// Collate
		$syntax .= ($fieldInfo['type'] == 'string' || $fieldInfo['type'] == 'text') ? ' COLLATE ' . $this->collate : '';
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
			$syntax .= " DEFAULT '" . $fieldInfo['default'] . "'";
		}
		// Extra
		$syntax .= ($fieldInfo['primary'] || $fieldInfo['auto_increment']) ? ' AUTO_INCREMENT' : '';
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
			// Determine key field name (can't use same key name twice, so we  have to append a number)
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
		$syntax .= "\n) ENGINE=" . $this->engine . " DEFAULT CHARSET=" . $this->charset . " COLLATE=" . $this->collate . ";";
		
		return $syntax;
	}
	
	
	/**
	 * Syntax for each column in CREATE TABLE command
	 *
	 * @param string $fieldName Field name
	 * @param array $fieldInfo Array of field settings
	 * @return string SQL syntax
	 */
	public function migrateSyntaxFieldUpdate($fieldName, array $fieldInfo)
	{
		return "CHANGE `" . $fieldName . "` " . $this->migrateSyntaxFieldCreate($fieldName, $fieldInfo);
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
		$syntax = "ALTER TABLE `" . $table . "` (\n";
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
		$syntax .= "\n) ENGINE=" . $this->engine . " DEFAULT CHARSET=" . $this->charset . " COLLATE=" . $this->collate . ";";
		
		return $syntax;
	}
	
	
	/**
	 * Genereate select SQL from query object
	 */
	public function selectSql()
	{	
		$sql = "
			SELECT #{?}.fields
			#{FROM ?}.table
			#{?}.joins
			#{WHERE ?}.conditions
			#{GROUP BY ?}.groups
			#{ORDER BY ?}.sorts
			";
	}
}