<?php

defined('SYSPATH') or die('No direct script access.');

class Model_Licence extends ORM {

	protected $_table_name = 'licences';
	protected $_table_columns = array(
		'id' => array(),
		'name' => array(),
		'link_url' => array(),
	);

}
