<?php

defined('SYSPATH') or die('No direct script access.');

class Model_People extends ORM {

	protected $_table_name = 'people';

	protected $_belongs_to = array(
		'auth_level'=>array('model'=>'AuthLevels'),
	);

	public function __get($column)
	{
		if ($column != 'most_recent_email')
		{
			return parent::__get($column);
		}
		else
		{
			return ORM::factory('emails')
				->where('to_id', '=', $this->id)
				->or_where('from_id', '=', $this->id)
				->order_by('date_and_time', 'DESC')
				->limit(1)
				->find();
		}
	}

	public function is_main_user()
	{
		return TWYNE_USER_ID == $this->id;
	}

}
