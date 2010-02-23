<?php
require_once dirname(__FILE__) . '/init.php';

/**
 * Tests to stress the Query adapter and how it handles conditions
 */
class ConditionsTest extends PHPUnit_Framework_TestCase
{
	protected $blogMapper;
	
	/**
	 * Prepare the data
	 */
	public static function setUpBeforeClass()
	{
		$m = new mysqli('localhost','test','password','test');
		$m->query('TRUNCATE TABLE test_blog');
		$m->query('TRUNCATE TABLE test_blog_comments');
		for( $i = 0; $i < 10; $i++ ) {
			$m->query("INSERT INTO test_blog(title, body, date_created) VALUES('title{$i}', 'body{$i}', '2010-01-{$i}')");
		}
	}
	
	/**
	 * Setup/fixtures for each test
	 */
	public function setUp()
	{
		// New mapper instance
		$this->blogMapper = fixture_mapper('Blog');
	}
	public function tearDown() {}
	
	public function testDefault()
	{
		$mapper = $this->blogMapper;
		$post = $mapper->first(array('id' => 2));
		$this->assertEquals( $post->id, 2 );
	}
	
	public function testEquals()
	{
		$mapper = $this->blogMapper;
		$post = $mapper->first(array('id =' => 2));
		$this->assertEquals( $post->id, 2 );
	}
	
	public function testArrayDefault() {
		$mapper = $this->blogMapper;
		$post = $mapper->first(array('id' => array(2)));
		$this->assertEquals( $post->id, 2 );
	}
	
	public function testArrayInSingle() {
		$mapper = $this->blogMapper;
		$post = $mapper->first(array('id IN' => array(2)));
		$this->assertEquals( $post->id, 2 );
		
		$post = $mapper->first(array('id IN' => array('a')));
		$this->assertFalse( $post );
	}
	
	public function testArrayNotInSingle() {
		$mapper = $this->blogMapper;
		$post = $mapper->first(array('id NOT IN' => array(2)));
		$this->assertEquals( $post->id, 1 );
	}
	
	public function testArrayIn() {
		$mapper = $this->blogMapper;
		$posts = $mapper->all(array('id IN' => array(3,4,5)));
		$this->assertEquals( $posts->count(), 3 );
	}
	
	public function testArrayNotIn() {
		$mapper = $this->blogMapper;
		$posts = $mapper->all(array('id NOT IN' => array(3,4,5)));
		$this->assertEquals( $posts->count(), 7 );
	}
	
	public function testOperators() {
		$mapper = $this->blogMapper;
		$this->assertFalse( $mapper->first(array('id <' => 1)) );
		$this->assertFalse( $mapper->first(array('id >' => 10)) );
		
		$this->assertEquals( $mapper->all(array('id <' => 5))->count(), 4 );
		$this->assertEquals( $mapper->all(array('id >=' => 5))->count(), 6 );
	}
}