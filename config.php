<?php

$CONFIG += array(		// Config parameters

	'DEBUG_KEYS' => array('1' => 1),	// password => debug_level hash

	'timezone' => 'Europe/Paris',
	'php' => 'c:/progra~1/wamp/php/php.exe', // Path to your php executable.
	'debug_email' => 'webmaster',

	'maxage' => 3600,
	'lang_list' => 'fr',

	'allow_debug' => true,

	'translate_driver' => 'default_',
	'translate_params' => array(),

);

if (!isset($CONFIG['DEBUG'])) $CONFIG['DEBUG'] = isset($_COOKIE['DEBUG']) ? (int) @$CONFIG['DEBUG_KEYS'][$_COOKIE['DEBUG']] : 0;

CIA_GO(__FILE__, true); // 2nd parameter: true if your server supports PATH_INFO, else false 
