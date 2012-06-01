<?php

defined('SYSPATH') or die('No direct script access.');

class Model_JournalEntries extends ORM {

	protected $_table_name = 'journal_entries';

	protected $_has_many = array(
		'tags'=>array(
			'model'=>'Tags',
			'through'=>'journal_entry_tags',
			'far_key'=>'tag_id',
			'foreign_key'=>'journal_entry_id'
		),
	);

	protected $_belongs_to = array(
		'auth_level'=>array('model'=>'AuthLevels'),
	);

}