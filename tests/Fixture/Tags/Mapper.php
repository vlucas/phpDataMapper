<?php
/**
 * Tags Mapper
 */
class Fixture_Tags_Mapper extends TestMapper
{
	protected $_datasource = 'test_tags';
	
	public $id = array('type' => 'int', 'primary' => true);
	public $name = array('type' => 'string', 'required' => true, 'unique' => true);
}