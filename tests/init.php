<?php
// Require PHPUnit
require_once 'PHPUnit/Framework.php';

// Require phpDataMapper
$baseDir = dirname(dirname(__FILE__));
require $baseDir . '/Base.php';
require $baseDir . '/Adapter/Mysql.php';

// Date setup
date_default_timezone_set('America/Chicago');


// Available adapters for testing
$testAdapters = array(
	'mysql' => array(
		'adapter' => 'phpDataMapper_Adapter_Mysql',
		'host' => 'localhost',
		'username' => 'root',
		'password' => null,
		'database' => 'test'
	),
	'mongo' => array(
		'adapter' => 'phpDataMapper_Adapter_NoSQL_Mongo',
		'host' => 'localhost:28017',
		'username' => null,
		'password' => null,
		'database' => 'test'
	)
);
$cli = cli_params();
$defaultAdapter = 'mysql';
$adapterType = isset($cli['adapter']) ? strtolower($cli['adapter']) : $defaultAdapter;


/**
 * Return database adapter for use
 * Really hate to have to do it this way... Those PHPUnit TestSuites should be far easier than they are...
 */
$fixture_adapter = array(); 
function fixture_adapter()
{
	global $fixture_adapter, $testAdapters, $adapterType; // Yikes, I know...
	
	if(!isset($testAdapters[$adapterType])) {
		throw new Exception("[ERROR] Unknown datasource adapter type '" . $adapterType . "'");
	}
	
	$adapter = $testAdapters[$adapterType];
	 
	// New adapter instance (connection) if one does not exist yet
	if(!isset($fixture_adapter[$adapterType])) {
		$adapterClass = $adapter['adapter'];
		$fixture_adapter[$adapterType] = new $adapterClass($adapter['host'], $adapter['database'], $adapter['username'], $adapter['password']);
	}
	return $fixture_adapter[$adapterType];
}

/**
 * Return mapper for use
 */
$fixture_mappers = array();
function fixture_mapper($mapperName)
{
	global $fixture_mappers; // I promise, globals are not used in the actual codebase, only for these tests...
	if(!isset($fixture_mappers[$mapperName])) {
		$mapperClass = 'Fixture_' . $mapperName . '_Mapper';
		$fixture_mappers[$mapperName] = new $mapperClass(fixture_adapter());
		$fixture_mappers[$mapperName]->migrate();
	}
	return $fixture_mappers[$mapperName];
}


/**
 * Return mapper for use
 */
function phpdm_test_autoloader($className) {
	// Don't attempt to autoload PHPUnit classes
	if(strpos($className, 'PHPUnit') !== false) {
		return false;
	}
	$classFile = str_replace('_', '/', $className) . '.php';
	require dirname(__FILE__) . '/' . $classFile;
}
spl_autoload_register('phpdm_test_autoloader');


/**
 * Parses $GLOBALS['argv'] for parameters and assigns them to an array.
 * @link http://us.php.net/manual/en/function.getopt.php#83414
 *
 * Supports:
 * -e
 * -e <value>
 * --long-param
 * --long-param=<value>
 * --long-param <value>
 * <value>
 *
 * @param array $noopt List of parameters without values
 */
function cli_params($noopt = array()) {
	$result = array();
	$params = $GLOBALS['argv'];
	// could use getopt() here (since PHP 5.3.0), but it doesn't work relyingly
	reset($params);
	while (list($tmp, $p) = each($params)) {
		if ($p{0} == '-') {
			$pname = substr($p, 1);
			$value = true;
			if ($pname{0} == '-') {
				// long-opt (--<param>)
				$pname = substr($pname, 1);
				if (strpos($p, '=') !== false) {
					// value specified inline (--<param>=<value>)
					list($pname, $value) = explode('=', substr($p, 2), 2);
				}
			}
			// check if next parameter is a descriptor or a value
			$nextparm = current($params);
			if (!in_array($pname, $noopt) && $value === true && $nextparm !== false && $nextparm{0} != '-') list($tmp, $value) = each($params);
			$result[$pname] = $value;
		} else {
			// param doesn't belong to any option
			$result[] = $p;
		}
	}
	return $result + $_GET;
}


/**
 *
 */
class TestMapper extends phpDataMapper_Base
{
	/*
	// Auto-migrate upon instantiation
	public function init()
	{
		$this->dropDatasource();
		$this->migrate();
	}
	*/
}
