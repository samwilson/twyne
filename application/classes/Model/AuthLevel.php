<?php

defined('SYSPATH') or die('No direct script access.');

class Model_AuthLevel extends ORM {

	protected $_table_name = 'auth_levels';

	protected $_table_columns = array('id'=>array(), 'name'=>array());

}