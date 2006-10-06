<?php

$CONFIG = array(

	// Parent's directory :
	//   string for single inheritance,
	//   array for multiple inheritance
	// If not set, defaults to "../../"
	// Uses C3 Method Resolution Order, like in Python 2.3.
	//'extends' => /*string|array*/,

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
