<?php

defined('SYSPATH') or die('No direct script access.');

class Model_Emails extends ORM {

	protected $_table_name = 'emails';

	protected $_belongs_to = array(
		'from'=>array('model'=>'people'),
		'to'=>array('model'=>'people'),
	);

	public function year()
	{
		return substr($this->date_and_time, 0, 4);
	}

}