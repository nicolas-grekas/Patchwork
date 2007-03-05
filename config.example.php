<?php

/* php.ini configuration :
 * For maximum performance and security,
 * copy/paste these directives at the end of the file.

magic_quotes_gpc = Off
register_globals = Off
register_long_arrays = Off
register_argc_argv = Off
auto_globals_jit = On

session.auto_start = Off

mbstring.language = neutral
mbstring.script_encoding = UTF-8
mbstring.encoding_translation = On
mbstring.http_input = UTF-8
mbstring.http_output = pass
mbstring.substitute_character = none

; mbstring's functions overloading prevents binary use of strings.
; You should directly use mb_*() functions instead.
mbstring.func_overload = 0

*/

$CONFIG += array(

	'DEBUG' => 1,
	'DEBUG_KEYS' => array('' => 1),	// password => debug_level hash

	// This is critical global config.
	'php' => '/usr/bin/php', // Path to your php (CLI) executable.
	'timezone' => 'Europe/Paris',

	// For sending emails. See PEAR's Mail::factory()
	'debug_email' => 'webmaster',
	'email_backend' => 'mail',
	'email_options' => '',


	// Next: defaults should be ok to start with

	'clientside' => true,
	'use_path_info' => true,

	'maxage' => 3600,
	'lang_list' => 'fr',

//	'translate_adapter' => false,
//	'translate_options' => array(),

);
