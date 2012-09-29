<?php

defined('SYSPATH') or die('No direct script access.');

require SYSPATH.'classes/kohana/core'.EXT;
require SYSPATH.'classes/kohana'.EXT;
spl_autoload_register(array('Kohana', 'auto_load'));
ini_set('unserialize_callback_func', 'spl_autoload_call');
I18n::lang(KOHANA_LANG);
Kohana::$environment = constant('Kohana::'.strtoupper(KOHANA_ENV));

/**
 * Initialize Kohana, setting the default options.
 *
 * The following options are available:
 *
 * - string   base_url    path, and optionally domain, of your application   NULL
 * - string   index_file  name of your index file, usually "index.php"       index.php
 * - string   charset     internal character set used for input and output   utf-8
 * - string   cache_dir   set the internal cache directory                   APPPATH/cache
 * - boolean  errors      enable or disable error handling                   TRUE
 * - boolean  profile     enable or disable internal profiling               TRUE
 * - boolean  caching     enable or disable internal caching                 FALSE
 */
Kohana::init(array(
	'base_url'=>KOHANA_BASE_URL,
	'index_file'=>FALSE,
));

/**
 * Attach the file write to logging. Multiple writers are supported.
 */
Kohana::$log->attach(new Kohana_Log_File(APPPATH.'logs'));

/**
 * Attach a file reader to config. Multiple readers are supported.
 */
Kohana::$config->attach(new Kohana_Config_File);

Upload::$default_directory = DATAPATH.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'IN';

/**
 * Enable modules. Modules are referenced by a relative or absolute path.
 */
Kohana::modules(array(
	'database'=>MODPATH.'database', // Database access
	'image'=>MODPATH.'image', // Image manipulation
	'orm'=>MODPATH.'orm', // Object Relationship Mapping
));

/**
 * Set the routes. Each route must have a minimum of a name, a URI and a set of
 * defaults for the URI.
 */
Route::set('dates', 'dates(/<year>(/<month>)(.<format>))', array(
	'year'=>'[0-9]{1,4}',
	'month'=> '[0-9]{1,2}',
	'format'=>'(html|pdf)',
))->defaults(array(
	'controller'=>'index',
	'action'=>'index',
	'format'=>'html',
));
Route::set('tags', 'tags(/<tag>)')->defaults(array(
	'controller'=>'index',
	'action'=>'tag',
));
Route::set('view', '<id>(.<format>)', array(
	'id' => '[0-9]+',
	'format'=>'(pdf|html)',
))->defaults(array(
	'controller'=>'image',
	'action'=>'view',
	'format' => 'html',
));
Route::set('render', '<id>(_<size>).(<format>)', array(
	'id' => '[0-9]+',
	'size' => '(thumb|view|full)',
	'format'=>'(png|jpg)',
))->defaults(array(
	'controller'=>'image',
	'action'=>'render',
	'size' => 'max',
	'format' => 'view',
));
Route::set('image', '<id>/<action>',array(
	'action' => '(edit|delete|save)'
))->defaults(array(
	'controller'=>'image',
	'action'=>'edit'
));
Route::set('upload', 'upload(/<filename>)', array(
	'filename'=>'.*'
))->defaults(array(
	'controller'=>'image',
	'action'=>'upload'
));
Route::set('people', 'people')->defaults(
		array('controller'=>'people', 'action'=>'index')
);
Route::set('person', 'person(/<id>)')->defaults(
		array('controller'=>'people', 'action'=>'edit', 'id'=>NULL)
);
Route::set('login', 'login')->defaults(
		array('controller'=>'people', 'action'=>'login')
);
Route::set('logout', 'logout')->defaults(
		array('controller'=>'people', 'action'=>'logout')
);
Route::set('home', '')->defaults(
		array('controller'=>'index', 'action'=>'index')
);
Route::set('default', '(<controller>(/<action>(/<id>(/<format>))))')->defaults(
		array(
			'controller'=>'index',
			'action'=>'index',
			'id'=>NULL,
			'format'=>NULL,
		)
);
