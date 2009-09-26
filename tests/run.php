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
	protected $sampleAdapter, $sampleNewsMaper;
	
	public function setUp() {
		// New db connection
		$this->sampleAdapter = new phpDataMapper_Database_Adapter_Mysql('localhost', 'test_news', 'root', '');
		
		// New mapper
		$this->sampleNewsMapper = new SampleNews($this->sampleAdapter);
	}
	
	public function testAdapterInstance() {
		$this->assertIsA($this->sampleAdapter, 'phpDataMapper_Database_Adapter_Abstract');
	}
	
	public function testMapperInstance() {
		$this->assertIsA($this->sampleNewsMapper, 'phpDataMapper_Model');
	}
	// @todo Need to get back raw SQL code that was run
	public function testTableMigrate() {
		$mapper = $this->sampleNewsMapper;
		$mapper->migrate();
		//$this->assert
	}
	
	public function testSampleNewsInsert() {
		$mapper = $this->sampleNewsMapper;
		$post = $mapper->get();
		$post->title = "Test Post";
		$post->body = "<p>This is a really awesome super-duper post.</p><p>It's really quite lovely.</p>";
		$post->date_created = date($mapper->getDateTimeFormat());
		$result = $mapper->save($post);
		$this->assertTrue($result);
	}
	
	public function testSampleNewsUpdate() {
		
	}
	
	public function testSampleNewsDelete() {
		
	}
	
	public function tearDown() {
		$this->sampleNewsMapper->truncateTable();
		//$this->sampleNewsMapper->dropTable();
	}
}