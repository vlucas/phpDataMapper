<?php
require_once dirname(dirname(__FILE__)) . '/init.php';


/**
 *
 */
class TestMapper extends phpDataMapper_Base
{
	// Auto-migrate upon instantiation
	public function init()
	{
		$this->migrate();
	}
}

/**
 * Blog Mapper
 * 
 * @todo Organize this a little better...
 */
class BlogMapper extends TestMapper {
	protected $source = 'test_blog';
	
	public $id = array('type' => 'int', 'primary' => true);
	public $title = array('type' => 'string', 'required' => true);
	public $body = array('type' => 'text', 'required' => true);
	public $date_created = array('type' => 'datetime');
	
	// Each post entity 'hasMany' comment entites
	public $comments = array(
		'type' => 'relation',
		'relation' => 'HasMany',
		'mapper' => 'BlogCommentsMapper',
		'where' => array('post_id' => 'entity.id'),
		'order' => array('date_created' => 'ASC')
		);
}
/**
 * Blog Comments Mapper
 * @todo implement 'BelongsTo' relation for linking back to blog post object
 */
class BlogCommentsMapper extends TestMapper {
	protected $source = 'test_blog_comments';
	
	public $id = array('type' => 'int', 'primary' => true);
	public $post_id = array('type' => 'int', 'index' => true);
	public $name = array('type' => 'string', 'required' => true);
	public $email = array('type' => 'string', 'required' => true);
	public $body = array('type' => 'text', 'required' => true);
	public $date_created = array('type' => 'datetime');
}


/**
 * Blog basic tests
 */
class Blog_BasicTest extends PHPUnit_Framework_TestCase
{
	protected $blogMapper;
	
	/**
	 * Setup/fixtures for each test
	 */
	public function setUp()
	{
		// New mapper instance
		$this->blogMapper = new BlogMapper(fixture_adapter());
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