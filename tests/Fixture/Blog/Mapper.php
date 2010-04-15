<?php
/**
 * Blog Mapper
 */
class Fixture_Blog_Mapper extends TestMapper
{
	protected $_datasource = 'test_blog';
	
	public $id = array('type' => 'int', 'primary' => true, 'serial' => true);
	public $title = array('type' => 'string', 'required' => true);
	public $body = array('type' => 'text', 'required' => true);
	public $date_created = array('type' => 'datetime');
	
	// Each post entity 'hasMany' comment entites
	public $comments = array(
		'type' => 'relation',
		'relation' => 'HasMany',
		'mapper' => 'Fixture_Blog_Comments_Mapper',
		'where' => array('post_id' => 'entity.id'),
		'order' => array('date_created' => 'ASC')
		);
	
	// Each post entity 'hasMany' tags through a 'post_tags' relationship
	public $tags = array(
		'type' => 'relation',
		'relation' => 'HasMany',
		'mapper' => 'Fixture_Blog_Tags_Mapper',
		'where' => array('post_id' => 'entity.id'),
		'through' => 'post_tags'
		);
}