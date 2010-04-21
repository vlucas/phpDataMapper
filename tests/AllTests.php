<?php
// Require PHPUnit
require_once dirname(__FILE__) . '/init.php';
require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'PHPUnit/TextUI/TestRunner.php';


/**
 * From Doctrine2's test suite code
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'AllTests::main');
}

class AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
		//PHPUnit_TextUI_Command::main();
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('phpDataMapper Tests');
		
		// Should probably just traverse the "Test" directory or something...
        $suite->addTestSuite('Test_CRUD');
		$suite->addTestSuite('Test_Conditions');
		$suite->addTestSuite('Test_Relations');
		
        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'AllTests::main') {
    AllTests::main();
}