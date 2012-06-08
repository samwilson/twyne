<?php

$database_config = array
	(
	'connection'=>array(
		/**
		 * The following options are available for MySQL:
		 *
		 * string   hostname     server hostname, or socket
		 * string   database     database name
		 * string   username     database username
		 * string   password     database password
		 * boolean  persistent   use persistent connections?
		 * array    variables    system variables as "key => value" pairs
		 *
		 * Ports and sockets may be appended to the hostname.
		 */
		'hostname'=>'localhost',
		'database'=>'twyne_dev',
		'username'=>'twyne_dev',
		'password'=>'twyne_dev123',
		'persistent'=>FALSE,
	),
	'table_prefix'=>'',
	'charset'=>'utf8',
	'caching'=>FALSE,
	'profiling'=>TRUE,
);

$email_config = array(
	'inbox'=>'INBOX',
	'server'=>'mail.example.com',
	'port'=>993,
	'username'=>'user@example.com',
	'password'=>'password123',
);

/**
 * The ID of the main user in the `people` table.
 */
define('TWYNE_USER_ID', 1);

define('RST2HTML_CMD', '/usr/bin/rst2html');

define('KOHANA_LANG', 'en-au');

define('KOHANA_ENV', 'production');

define('KOHANA_ERROR_REPORTING', E_ALL);

define('KOHANA_BASE_URL', '/twyne/');

define('KOHANA_DATA_DIRECTORY', '/var/www/twyne-data');

/**
 * Set the default time zone.
 *
 * @see  http://kohanaframework.org/guide/using.configuration
 * @see  http://php.net/timezones
 */
date_default_timezone_set('Australia/Perth');

/**
 * Set the default locale.
 *
 * @see  http://kohanaframework.org/guide/using.configuration
 * @see  http://php.net/setlocale
 */
setlocale(LC_ALL, 'en_AU.utf-8');
