<?php
// Init / Setup
require dirname(__FILE__) . '/init.php';

// Require Blog tests
require dirname(__FILE__) . '/Blog/AllTests.php';


/**
 * Run all tests
 */
class AllTests
{
    public static function suite()
    {
        $suite = new MapperTestSuite('phpDataMapper Tests');
        
        $blogSuite = new Blog_AllTests('Blog Tests');
        $suite->addTest($blogSuite);
        
        return $suite;
    }
}


/**
 * Mapper test suite to maintain connections for all tests
 */
class MapperTestSuite extends PHPUnit_Framework_TestSuite
{
    protected function setUp()
    {
        
    }
 
    protected function tearDown()
    {
        $this->adapter = null;
    }
}