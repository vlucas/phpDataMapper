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
		for( $i = 1; $i <= self::num_posts; $i++ ) {
			$blogMapper->insert(array(
				'title' => ($i % 2 ? 'odd' : 'even' ). '_title',
				'body' => '<p>' . $i  . '_body</p>',
				'status' => $i ,
				'date_created' => $blogMapper->adapter()->dateTime()
			));
		}
	}
	
	
	public function testDefault()
	{
		$mapper = fixture_mapper('Blog');
		$post = $mapper->first(array('status' => 2));
		$this->assertEquals( $post->status, 2 );
	}
	
	public function testOperatorEquals()
	{
		$mapper = fixture_mapper('Blog');
		$post = $mapper->first(array('status =' => 2));
		$post = $mapper->first(array('status :eq' => 2));
		$this->assertEquals( $post->status, 2 );
	}
	
	public function testArrayDefault()
	{
		$mapper = fixture_mapper('Blog');
		$post = $mapper->first(array('status' => array(2)));
		$this->assertEquals( $post->status, 2 );
	}
	
	public function testArrayInSingle()
	{
		$mapper = fixture_mapper('Blog');
		
		// Numeric
		$post = $mapper->first(array('status :in' => array(2)));
		$this->assertEquals( $post->status, 2 );
		
		// Alpha
		$post = $mapper->first(array('status :in' => array('a')));
		$this->assertFalse( $post );
	}
	
	public function testArrayNotInSingle()
	{
		$mapper = fixture_mapper('Blog');
		$post = $mapper->first(array('status :not' => array(2)));
		$this->assertEquals( $post->status, 1 );
	}
	
	public function testArrayMultiple()
	{
		$mapper = fixture_mapper('Blog');
		$posts = $mapper->all(array('status' => array(3,4,5)));
		$posts = $mapper->all(array('status :in' => array(3,4,5)));
		$this->assertEquals( $posts->count(), 3 );
	}
	
	public function testArrayNotInMultiple()
	{
		$mapper = fixture_mapper('Blog');
		$posts = $mapper->all(array('status :not' => array(3,4,5)));
		$this->assertEquals( $posts->count(), self::num_posts - 3 );
	}
	
	// Less than
	public function testOperatorLt()
	{
		$mapper = fixture_mapper('Blog');
		$result = $mapper->first(array('status <' => 1));
		$this->assertFalse( $result );
		//$this->assertFalse( $mapper->first(array('status :lt' => 1)) );
	}
	
	// Less than #2
	public function testOperatorsLt2()
	{
		$mapper = fixture_mapper('Blog');
		$this->assertEquals( $mapper->all(array('status <' => 5))->count(), 4 );
		$this->assertEquals( $mapper->all(array('status :lt' => 5))->count(), 4 );
	}
	
	// Greater than
	public function testOperatorGt()
	{
		$mapper = fixture_mapper('Blog');
		$this->assertFalse( $mapper->first(array('status >' => self::num_posts)) );
		$this->assertFalse( $mapper->first(array('status :gt' => self::num_posts)) );
	}
	
	// Greater than or equal to
	public function testOperatorsGte()
	{
		$mapper = fixture_mapper('Blog');
		
		$this->assertEquals( $mapper->all(array('status >=' => 5))->count(), self::num_posts - 4 );
		$this->assertEquals( $mapper->all(array('status :gte' => 5))->count(), self::num_posts - 4 );
	}
	
	// These only work for SQL databases... Need to find a more abstract solution for this
	// Not sure if these should even be included here - they are more of a hack than anything else... but they work. Nice.
	/*
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
	*/
}
