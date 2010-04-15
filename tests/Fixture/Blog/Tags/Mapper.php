<?php
/**
 * Blog Tags Mapper
 * Relates tags to blog posts through proxy table
 */
class Fixture_Blog_Tags_Mapper extends TestMapper
{
	protected $_datasource = 'test_blog_tags';
	
	public $post_id = array('type' => 'int', 'primary' => true, 'serial' => true);
	public $tag_id = array('type' => 'int', 'key' => true);
	
	public $post_tags = array(
		'type' => 'relation',
		'relation' => 'HasMany',
		'mapper' => 'Fixture_Tag_Mapper',
		'where' => array('tag_id' => 'entity.tag_id')
		);
	
	/*
	public $post = array(
		'type' => 'relation',
		'relation' => 'BelongsTo',
		'mapper' => 'Fixture_Blog_Mapper',
		'where' => array('post_id' => 'entity.post_id')
		);
	*/
}