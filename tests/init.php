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
		$fixture_adapter = new phpDataMapper_Adapter_Mysql('localhost', 'test', 'test', 'password');
	}
	return $fixture_adapter;
}