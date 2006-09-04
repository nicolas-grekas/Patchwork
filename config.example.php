<?php

$CONFIG += array(		// Config parameters

	'DEBUG_KEYS' => array('' => 1),	// password => debug_level hash

	'inheritance_optimization' => 'inline', // 'inline', 'include' or false
	'timezone' => 'Europe/Paris',
	'php' => 'c:/progra~1/wamp/php/php.exe', // Path to your php executable.
	'debug_email' => 'webmaster',

	'maxage' => 3600,
	'lang_list' => 'fr',

	'allow_debug' => true,

	'translate_driver' => 'default_',
	'translate_params' => array(),

);

if (!isset($CONFIG['DEBUG'])) $CONFIG['DEBUG'] = (int) @$CONFIG['DEBUG_KEYS'][(string)$_COOKIE['DEBUG']];

CIA_GO(__FILE__, true); // 2nd parameter: true if your server supports PATH_INFO, else false 
