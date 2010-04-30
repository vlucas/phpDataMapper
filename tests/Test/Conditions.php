<?php
/**
 * Tests to stress the Query adapter and how it handles conditions
 */
class Test_Conditions extends PHPUnit_Framework_TestCase
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
				'date_created' => $blogMapper->adapter()->dateTime()
			));
		}
	}
	
	
	public function testDefault()
	{
		$mapper = fixture_mapper('Blog');
		$post = $mapper->first(array('id' => 2));
		$this->assertEquals( $post->id, 2 );
	}
	
	public function testOperatorEquals()
	{
		$mapper = fixture_mapper('Blog');
		$post = $mapper->first(array('id =' => 2));
		$this->assertEquals( $post->id, 2 );
	}
	
	public function testArrayDefault()
	{
		$mapper = fixture_mapper('Blog');
		$post = $mapper->first(array('id' => array(2)));
		$this->assertEquals( $post->id, 2 );
	}
	
	public function testArrayInSingle()
	{
		$mapper = fixture_mapper('Blog');
		
		// Numeric
		$post = $mapper->first(array('id IN' => array(2)));
		$this->assertEquals( $post->id, 2 );
		
		// Alpha
		$post = $mapper->first(array('id' => array('a')));
		$this->assertFalse( $post );
	}
	
	public function testArrayNotInSingle()
	{
		$mapper = fixture_mapper('Blog');
		$post = $mapper->first(array('id NOT IN' => array(2)));
		$this->assertEquals( $post->id, 1 );
	}
	
	public function testArrayMultiple()
	{
		$mapper = fixture_mapper('Blog');
		$posts = $mapper->all(array('id' => array(3,4,5)));
		$this->assertEquals( $posts->count(), 3 );
	}
	
	public function testArrayNotInMultiple()
	{
		$mapper = fixture_mapper('Blog');
		$posts = $mapper->all(array('id NOT IN' => array(3,4,5)));
		$this->assertEquals( $posts->count(), self::num_posts - 3 );
	}
	
	// Less than
	public function testOperatorLt()
	{
		$mapper = fixture_mapper('Blog');
		$this->assertFalse( $mapper->first(array('id <' => 1)) );
	}
	
	// Less than #2
	public function testOperatorsLt2()
	{
		$mapper = fixture_mapper('Blog');
		$this->assertEquals( $mapper->all(array('id <' => 5))->count(), 4 );
	}
	
	// Greater than
	public function testOperatorGt()
	{
		$mapper = fixture_mapper('Blog');
		$this->assertFalse( $mapper->first(array('id >' => self::num_posts)) );
	}
	
	// Greater than or equal to
	public function testOperatorsGte()
	{
		$mapper = fixture_mapper('Blog');
		$this->assertEquals( $mapper->all(array('id >=' => 5))->count(), self::num_posts - 4 );
	}
	
	// These only work for SQL databases... Need to find a more abstract solution for this
	// Not sure if these should even be included here - they are more of a hack than anything else
	public function testMathFunctions()
	{
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
