<?php
/**
 * Blog Comments Mapper
 * @todo implement 'BelongsTo' relation for linking back to blog post object
 */
class Fixture_Blog_Comments_Mapper extends TestMapper
{
	protected $_datasource = 'test_blog_comments';
	
	public $id = array('type' => 'int', 'primary' => true, 'serial' => true);
	public $post_id = array('type' => 'int', 'index' => true, 'required' => true);
	public $name = array('type' => 'string', 'required' => true);
	public $email = array('type' => 'string', 'required' => true);
	public $body = array('type' => 'text', 'required' => true);
	public $date_created = array('type' => 'datetime');
}