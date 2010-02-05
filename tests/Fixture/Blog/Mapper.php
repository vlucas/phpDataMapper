<?php
/**
 * Blog Mapper
 */
class Fixture_Blog_Mapper extends TestMapper {
	protected $source = 'test_blog';
	
	public $id = array('type' => 'int', 'primary' => true);
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
}