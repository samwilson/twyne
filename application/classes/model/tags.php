<?php

defined('SYSPATH') or die('No direct script access.');

class Model_Tags extends ORM {

	protected $_table_name = 'tags';

	protected $_has_many = array(
		'images'=>array('model'=>'images', 'through'=>'tags_to_images', 'far_key'=>'image', 'foreign_key'=>'tag'),
		'journal_entries'=>array('model'=>'journal_entries', 'through'=>'tags_to_journal_entries', 'far_key'=>'journal_entry', 'foreign_key'=>'tag'),
	);

	public function get_list()
	{
		$out = array();
		$tags = $this->order_by('name')->find_all();
		foreach ($tags as $tag)
		{
			$out[] = $tag->name;
		}
		return implode(', ', $out);
	}

}
