<?php
require_once dirname(__FILE__) . '/init.php';

/**
 * Tests to stress the Query adapter and how it handles conditions
 */
class ConditionsTest extends PHPUnit_Framework_TestCase
{
	protected $backupGlobals = false;
	const num_posts = 10;
	
	/**
	 * Prepare the data
	 */
	public static function setUpBeforeClass()
	{
		$blogMapper = fixture_mapper('Blog');
		$blogMapper->truncateDatasource();
		
		$blogCommentsMapper = fixture_mapper('Blog_Comments');
		$blogCommentsMapper->truncateDatasource();
		
		// Insert blog dummy data
		for( $i = 0; $i < self::num_posts; $i++ ) {
			$blogMapper->insert(array(
				'title' => $i,
				'body' => $i,
				'date_created' => date($blogMapper->adapter()->dateFormat())
			));
		}
	}
	
	
	public function testDefault()
	{
		$mapper = fixture_mapper('Blog');
		$post = $mapper->first(array('id' => 2));
		$this->assertEquals( $post->id, 2 );
	}
	
	public function testEquals()
	{
		$mapper = fixture_mapper('Blog');
		$post = $mapper->first(array('id =' => 2));
		$this->assertEquals( $post->id, 2 );
	}
	
	public function testArrayDefault() {
		$mapper = fixture_mapper('Blog');
		$post = $mapper->first(array('id' => array(2)));
		$this->assertEquals( $post->id, 2 );
	}
	
	public function testArrayInSingle() {
		$mapper = fixture_mapper('Blog');
		$post = $mapper->first(array('id IN' => array(2)));
		$this->assertEquals( $post->id, 2 );
		
		$post = $mapper->first(array('id IN' => array('a')));
		$this->assertFalse( $post );
	}
	
	public function testArrayNotInSingle() {
		$mapper = fixture_mapper('Blog');
		$post = $mapper->first(array('id NOT IN' => array(2)));
		$this->assertEquals( $post->id, 1 );
	}
	
	public function testArrayIn() {
		$mapper = fixture_mapper('Blog');
		$posts = $mapper->all(array('id IN' => array(3,4,5)));
		$this->assertEquals( $posts->count(), 3 );
	}
	
	public function testArrayNotIn() {
		$mapper = fixture_mapper('Blog');
		$posts = $mapper->all(array('id NOT IN' => array(3,4,5)));
		$this->assertEquals( $posts->count(), self::num_posts - 3 );
	}
	
	public function testOperators() {
		$mapper = fixture_mapper('Blog');
		$this->assertFalse( $mapper->first(array('id <' => 1)) );
		$this->assertFalse( $mapper->first(array('id >' => self::num_posts)) );
		
		$this->assertEquals( $mapper->all(array('id <' => 5))->count(), 4 );
		$this->assertEquals( $mapper->all(array('id >=' => 5))->count(), self::num_posts - 4 );
	}
	
	public function testMathFunctions() {
		$mapper = fixture_mapper('Blog');
		try {
			$this->assertEquals( $mapper->first(array('SQRT(id)' => 2))->id, 4 );
			$this->assertEquals( $mapper->first(array('COS(id-1)' => 1))->id, 1 );
			$this->assertEquals( $mapper->first(array('COS(id-1) + COS(id-1) =' => 2))->id, 1 );
		} catch(Exception $e) {
			$mapper->debug();
		}
	}
}
