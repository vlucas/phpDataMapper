<?php
require_once dirname(__FILE__) . '/init.php';

/**
 * Basic CRUD Tests
 * Create, Read, Update, Delete
 */
class CRUDTest extends PHPUnit_Framework_TestCase
{
	protected $backupGlobals = false;
	protected $blogMapper;
	
	/**
	 * Setup/fixtures for each test
	 */
	public function setUp()
	{
		// New mapper instance
		$this->blogMapper = fixture_mapper('Blog');
	}
	public function tearDown() {}
	
	
	public function testAdapterInstance()
	{
		$this->assertTrue(fixture_adapter() instanceof phpDataMapper_Adapter_Interface);
	}
	
	public function testMapperInstance()
	{
		$this->assertTrue($this->blogMapper instanceof phpDataMapper_Base);
	}
	
	public function testSampleNewsInsert()
	{
		$mapper = $this->blogMapper;
		$post = $mapper->get();
		$post->title = "Test Post";
		$post->body = "<p>This is a really awesome super-duper post.</p><p>It's really quite lovely.</p>";
		$post->date_created = date($mapper->adapter()->dateTimeFormat());
		$result = $mapper->insert($post); // returns an id
		
		$this->assertTrue(is_numeric($result));
	}
	
	public function testSampleNewsInsertWithEmpyNonRequiredFields()
	{
		$mapper = $this->blogMapper;
		$post = $mapper->get();
		$post->title = "Test Post With Empty Values";
		$post->body = "<p>Test post here.</p>";
		$post->date_created = null;
		try {
			$result = $mapper->insert($post); // returns an id
		} catch(Exception $e) {
			$mapper->debug();
		}
		
		$this->assertTrue(is_numeric($result));
	}
	
	public function testSampleNewsUpdate()
	{
		$mapper = $this->blogMapper;
		$post = $mapper->first(array('title' => "Test Post"));
		
		$this->assertTrue($post instanceof phpDataMapper_Entity);
		
		$post->title = "Test Post Modified";
		$result = $mapper->update($post); // returns boolean
		
		$this->assertTrue($result);
	}
	
	public function testSampleNewsDelete()
	{
		$mapper = $this->blogMapper;
		$post = $mapper->first(array('title' => "Test Post Modified"));
		$result = $mapper->delete($post);
		
		$this->assertTrue($result);
	}
}