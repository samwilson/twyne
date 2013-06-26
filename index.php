<?php

/**
 * Try to load the config file.
 * 
 * @see README.md
 */
$config_filename = 'config.php';
if (!file_exists($config_filename)) {
    header("HTTP/1.1 500 Internal Server Error");
    echo "Unable to load configuration from $config_filename";
    exit(1);
}
require_once('config.php');
if (!defined('TWYNE_AUTOLOGIN'))
{
	define('TWYNE_AUTOLOGIN', FALSE);
}

/**
 * The directory in which your application specific resources are located.
 * The application directory must contain the bootstrap.php file.
 *
 * @see  http://kohanaframework.org/guide/about.install#application
 */
$application = 'application';

/**
 * The directory in which your modules are located.
 *
 * @see  http://kohanaframework.org/guide/about.install#modules
 */
$modules = 'modules';

/**
 * The directory in which the Kohana resources are located. The system
 * directory must contain the classes/kohana.php file.
 *
 * @see  http://kohanaframework.org/guide/about.install#system
 */
$system = 'system';

/**
 * The default extension of resource files. If you change this, all resources
 * must be renamed to use the new extension.
 *
 * @see  http://kohanaframework.org/guide/about.install#ext
 */
define('EXT', '.php');



/**
 * End of standard configuration! Changing any of the code below should only be
 * attempted by those with a working knowledge of Kohana internals.
 *
 * @see  http://kohanaframework.org/guide/using.configuration
 */
// Set the full path to the docroot
define('DOCROOT', realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR);

// Make the application relative to the docroot, for symlink'd index.php
if (!is_dir($application) AND is_dir(DOCROOT . $application))
    $application = DOCROOT . $application;

// Make the modules relative to the docroot, for symlink'd index.php
if (!is_dir($modules) AND is_dir(DOCROOT . $modules))
    $modules = DOCROOT . $modules;

// Make the system relative to the docroot, for symlink'd index.php
if (!is_dir($system) AND is_dir(DOCROOT . $system))
    $system = DOCROOT . $system;

// Define the absolute paths for configured directories
define('APPPATH', realpath($application) . DIRECTORY_SEPARATOR);
define('MODPATH', realpath($modules) . DIRECTORY_SEPARATOR);
define('SYSPATH', realpath($system) . DIRECTORY_SEPARATOR);
define('DATAPATH', realpath(KOHANA_DATA_DIRECTORY) . DIRECTORY_SEPARATOR);

// Clean up the configuration vars
unset($application, $modules, $system);

/**
 * Define the start time of the application, used for profiling.
 */
if (!defined('KOHANA_START_TIME')) {
    define('KOHANA_START_TIME', microtime(TRUE));
}

/**
 * Define the memory usage at the start of the application, used for profiling.
 */
if (!defined('KOHANA_START_MEMORY')) {
    define('KOHANA_START_MEMORY', memory_get_usage());
}

// Bootstrap the application
require APPPATH . 'bootstrap' . EXT;

if (PHP_SAPI == 'cli') // Try and load minion
{
	set_exception_handler(array('Kohana_Minion_Exception', 'handler'));
	Minion_Task::factory(Minion_CLI::options())->execute();
}
else
{
	/**
	 * Execute the main request. A source of the URI can be passed, eg: $_SERVER['PATH_INFO'].
	 * If no source is specified, the URI will be automatically detected.
	 */
	echo Request::factory()
		->execute()
		->send_headers(TRUE)
		->body();
}
