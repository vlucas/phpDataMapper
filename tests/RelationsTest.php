<?php
require_once dirname(__FILE__) . '/init.php';

/**
 * Basic CRUD Tests
 * Create, Read, Update, Delete
 */
class RelationsTest extends PHPUnit_Framework_TestCase
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
	public function testBlogCommentsRelationInsertByObject($postId)
	{
		$post = $this->blogMapper->get($postId);
		$commentMapper = fixture_mapper('Blog_Comments');
		
		// Array will usually come from POST/JSON data or other source
		$commentSaved = false;
		$comment = $commentMapper->get()
			->data(array(
				'post_id' => $postId,
				'name' => 'Testy McTester',
				'email' => 'test@test.com',
				'body' => 'This is a test comment. Yay!',
				'date_created' => date($commentMapper->adapter()->dateTimeFormat())
			));
		try {
			$commentSaved = $commentMapper->save($comment);
			if(!$commentSaved) {
				print_r($commentMapper->errors());
				$this->fail("Comment NOT saved");
			}
		} catch(Exception $e) {
			echo $e->getTraceAsString();
			$commentMapper->debug();
			exit();
		}
		$this->assertTrue($commentSaved !== false);
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
	public function testBlogCommentsRelationReturnsRelationObject($postId)
	{
		$post = $this->blogMapper->get($postId);
		$this->assertTrue($post->comments instanceof phpDataMapper_Relation);
	}
	
	/**
	 * @depends testBlogPostInsert
	 */
	public function testBlogCommentsRelationCanBeModified($postId)
	{
		$post = $this->blogMapper->get($postId);
		$sortedComments = $post->comments->order(array('date_created' => 'DESC'));
		$this->assertTrue($sortedComments instanceof phpDataMapper_Query);
	}
}