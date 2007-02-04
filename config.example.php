<?php

$CONFIG += array(

	'DEBUG' => 1,
	'DEBUG_KEYS' => array('' => 1),	// password => debug_level hash

	// This is critical global config.
	'php' => '/usr/bin/php', // Path to your php (CLI) executable.
	'timezone' => 'Europe/Paris',

	// For sending emails. See PEAR's Mail::factory()
	'debug_email' => 'webmaster',
	'email_driver' => 'mail',
	'email_options' => '',


	// Next: defaults should be ok to start with

	'clientside' => true,
	'use_path_info' => true,

	'maxage' => 3600,
	'lang_list' => 'fr',

//	'translate_driver' => false,
//	'translate_options' => array(),

);
