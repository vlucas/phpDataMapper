<?php
// Require PHPUnit
require_once 'PHPUnit/Framework.php';

// Require phpDataMapper
$baseDir = dirname(dirname(__FILE__));
require $baseDir . '/Base.php';
require $baseDir . '/Adapter/Mysql.php';

// Date setup
date_default_timezone_set('America/Chicago');


/**
 * Return database adapter for use
 * Really hate to have to do it this way... Those TestSuites should be far easier than they are...
 */
$fixture_adapter = null; 
function fixture_adapter()
{
	global $fixture_adapter; // Yikes, I know...
	if($fixture_adapter === null) {
		// New db connection
		$fixture_adapter = new phpDataMapper_Adapter_Mysql('localhost', 'test', 'root', '');
	}
	return $fixture_adapter;
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
	}
	return $fixture_mappers[$mapperName];
}


/**
 * Return mapper for use
 */
function phpdm_test_autoloader($className) {
	$classFile = str_replace('_', '/', $className) . '.php';
	require dirname(__FILE__) . '/' . $classFile;
}
spl_autoload_register('phpdm_test_autoloader');


/**
 *
 */
class TestMapper extends phpDataMapper_Base
{
	// Auto-migrate upon instantiation
	public function init()
	{
		$this->migrate();
	}
}
