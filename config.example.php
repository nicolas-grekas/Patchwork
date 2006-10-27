<?php

$CONFIG += array(

	//'DEBUG' => 1,
	'DEBUG_KEYS' => array('' => 1),	// password => debug_level hash


	// This is critical global config.
	'php' => 'c:/progra~1/wamp/php/php.exe', // Path to your php (CLI) executable.

	// This is cutomization
	'timezone' => 'Europe/Paris',
	'debug_email' => 'webmaster',


	// Next: defaults should be to start with

	'clientside' => true,
	'use_path_info' => true,
	'inheritance_optimization' => 'inline', // 'inline', 'include' or false

	'maxage' => 3600,
	'lang_list' => 'fr',

	'translate_driver' => 'default_',
	'translate_params' => array(),

);
