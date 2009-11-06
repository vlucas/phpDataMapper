<?php
// Require PHPUnit
require_once 'PHPUnit/Framework.php';

// Require phpDataMapper
$baseDir = dirname(dirname(__FILE__));
require $baseDir . '/Base.php';
require $baseDir . '/Adapter/Mysql.php';

date_default_timezone_set('America/Chicago');

// Sample news mapper
class SampleNews extends phpDataMapper_Base {
	protected $source = 'test_news';
	protected $fields = array(
		'id' => array('type' => 'int', 'primary' => true),
		'title' => array('type' => 'string', 'required' => true),
		'body' => array('type' => 'text', 'required' => true),
		'date_created' => array('type' => 'datetime')
		);
}

// MySQL Test Cases
class AdapterMysqlTestCase extends PHPUnit_Framework_TestCase {
	protected $sampleAdapter;
	protected $sampleNewsMaper;
	
	public function __construct() {
		// New db connection
		$this->sampleAdapter = new phpDataMapper_Adapter_Mysql('localhost', 'test', 'test', 'password');
		
		// New mapper
		$this->sampleNewsMapper = new SampleNews($this->sampleAdapter);
		$this->sampleNewsMapper->migrate();
	}
	public function setUp() {}
	public function tearDown() {}
	
	public function testAdapterInstance() {
		$this->assertTrue($this->sampleAdapter instanceof phpDataMapper_Adapter_Abstract);
	}
	
	public function testMapperInstance() {
		$this->assertTrue($this->sampleNewsMapper instanceof phpDataMapper_Base);
	}
	
	public function testSampleNewsInsert() {
		$mapper = $this->sampleNewsMapper;
		$post = $mapper->get();
		$post->title = "Test Post";
		$post->body = "<p>This is a really awesome super-duper post.</p><p>It's really quite lovely.</p>";
		$post->date_created = date($mapper->getDateTimeFormat());
		$result = $mapper->insert($post); // returns an id
		$this->assertTrue(is_numeric($result));
	}
	
	public function testSampleNewsUpdate() {
		$mapper = $this->sampleNewsMapper;
		$post = $mapper->first(array('title' => "Test Post"));
		$post->title = "Test Post Modified";
		$result = $mapper->update($post); // returns boolean
		$this->assertTrue($result);
	}
	
	public function testSampleNewsDelete() {
		$mapper = $this->sampleNewsMapper;
		$post = $mapper->first(array('title' => "Test Post Modified"));
		$result = $mapper->destroy($post);
		$this->assertTrue($result);
	}
}