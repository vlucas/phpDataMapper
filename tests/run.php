<?php
// Include SimpleTest
require 'simpletest/autorun.php';

// Include phpDataMapper
require '../Model.php';
require '../Database/Adapter/Mysql.php';

// Sample news mapper
class SampleNews extends phpDataMapper_Model {
	protected $table = 'test_news';
	protected $fields = array(
		'id' => array('type' => 'int', 'primary' => true),
		'title' => array('type' => 'string', 'required' => true),
		'body' => array('type' => 'text', 'required' => true),
		'date_created' => array('type' => 'datetime')
		);
}

// MySQL Test Cases
class AdapterMysqlTestCase extends UnitTestCase {
	protected $sampleAdapter;
	protected $sampleNewsMaper;
	
	public function __construct() {
		// New db connection
		$this->sampleAdapter = new phpDataMapper_Database_Adapter_Mysql('localhost', 'test', 'test', 'password');
		
		// New mapper
		$this->sampleNewsMapper = new SampleNews($this->sampleAdapter);
		$this->sampleNewsMapper->migrate();
	}
	public function setUp() {}
	public function tearDown() {}
	
	public function testAdapterInstance() {
		$this->assertIsA($this->sampleAdapter, 'phpDataMapper_Database_Adapter_Abstract');
	}
	
	public function testMapperInstance() {
		$this->assertIsA($this->sampleNewsMapper, 'phpDataMapper_Model');
	}
	
	public function testSampleNewsInsert() {
		$mapper = $this->sampleNewsMapper;
		$post = $mapper->get();
		$post->title = "Test Post";
		$post->body = "<p>This is a really awesome super-duper post.</p><p>It's really quite lovely.</p>";
		$post->date_created = date($mapper->getDateTimeFormat());
		$result = $mapper->insert($post);
		$this->assertTrue($result);
	}
	
	public function testSampleNewsUpdate() {
		$mapper = $this->sampleNewsMapper;
		$post = $mapper->first(array('title' => "Test Post"));
		$post->title = "Test Post Modified";
		$result = $mapper->update($post);
		$this->assertTrue($result);
	}
	
	public function testSampleNewsDelete() {
		$mapper = $this->sampleNewsMapper;
		$post = $mapper->first(array('title' => "Test Post Modified"));
		$result = $mapper->destroy($post);
		$this->assertTrue($result);
	}
}