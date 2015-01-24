<?php

defined('SYSPATH') or die('No direct script access.');

class Model_Person extends ORM {

	protected $_table_name = 'people';

	protected $_table_columns = array(
		'id' => array(),
		'name' => array(),
		'email_address' => array(),
		'notes' => array(),
		'auth_level_id' => array(),
		'openid_identity' => array(),
	);

	protected $_belongs_to = array(
		'auth_level'=>array('model'=>'AuthLevel'),
	);

	public function is_main_user()
	{
		return TWYNE_USER_ID == $this->id;
	}

}
