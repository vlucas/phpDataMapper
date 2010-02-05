<?php
require_once dirname(__FILE__) . '/init.php';

/**
 * Basic CRUD Tests
 * Create, Read, Update, Delete
 */
class RelationsTest extends PHPUnit_Framework_TestCase
{
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
	
	
	public function testBlogPostInsert()
	{
		$post = $this->blogMapper->get();
		$post->title = "My Awesome Blog Post";
		$post->body = "<p>This is a really awesome super-duper post.</p><p>It's testing the relationship functions.</p>";
		$post->date_created = date($this->blogMapper->adapter()->dateTimeFormat());
		$postId = $this->blogMapper->save($post);
		
		$this->assertTrue(is_numeric($postId));
		
		// Test selcting it to ensure it exists
		$postx = $this->blogMapper->get($postId);
		$this->assertTrue($postx instanceof phpDataMapper_Entity);
		
		return $postId;
	}
	
	/**
	 * @depends testBlogPostInsert
	 */
	public function testBlogCommentsRelationInsertByArray($postId)
	{
		$post = $this->blogMapper->get($postId);
		
		// Array will usually come from POST/JSON data or other source
		$post->comments = array(
			0 => array(
				'post_id' => $postId,
				'name' => 'Testy McTester',
				'email' => 'test@test.com',
				'body' => 'This is a test comment. Yay!',
				'date_created' => date($this->blogMapper->adapter()->dateTimeFormat())
				)
			);
		try {
			$this->blogMapper->save($post);
		} catch(Exception $e) {
			echo $e->getTraceAsString();
			$this->blogMapper->debug();
			exit();
		}
		$this->assertTrue($post->comments instanceof phpDataMapper_Query);
	}
	
	/**
	 * @depends testBlogPostInsert
	 */
	public function testBlogCommentsRelationCountOne($postId)
	{
		$post = $this->blogMapper->get($postId);
		$this->assertTrue(count($post->comments) == 1);
	}
	
	/**
	 * @depends testBlogPostInsert
	 */
	public function testBlogCommentsRelationReturnsQueryObject($postId)
	{
		$post = $this->blogMapper->get($postId);
		$this->assertTrue($post->comments instanceof phpDataMapper_Query);
	}
	
	/**
	 * @depends testBlogPostInsert
	 */
	public function testBlogCommentsRelationCanBeModified($postId)
	{
		$post = $this->blogMapper->get($postId);
		$sortedComments = $post->comments->order(array('date_created' => 'DESC'));
		$this->assertTrue($post->comments instanceof phpDataMapper_Query);
	}
}