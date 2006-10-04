<?php

$CONFIG = array(

	// For multiple inheritance, put an array in $CONFIG['extends'].
	// If not set, defaults to "../../config.php"
	// Uses C3 Method Resolution Order, like in Python 2.3.
	'extends' => false,

	'DEBUG_KEYS' => array('' => 1),	// password => debug_level hash

	'use_path_info' => true,
	'inheritance_optimization' => 'inline', // 'inline', 'include' or false
	'timezone' => 'Europe/Paris',
	'php' => 'c:/progra~1/wamp/php/php.exe', // Path to your php (CLI) executable.
	'debug_email' => 'webmaster',

	'maxage' => 3600,
	'lang_list' => 'fr',

	'translate_driver' => 'default_',
	'translate_params' => array(),

);
