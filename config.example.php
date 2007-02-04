<?php

/* If possible, copy/paste these directives at the end of your php.ini :

; Replace this to your needs
error_log = /tmp/php_error.log

log_errors = On
display_errors = Off
zlib.output_compression = Off
magic_quotes_gpc = Off
magic_quotes_runtime = Off

variables_order = "GPCES"
register_globals = Off
register_long_arrays = Off
register_argc_argv = Off
auto_globals_jit = On

session.auto_start = Off
session.use_only_cookies = On
session.use_cookies = Off
session.use_trans_sid = Off

mbstring.language = neutral
mbstring.script_encoding = UTF-8
mbstring.internal_encoding = UTF-8

mbstring.encoding_translation = On
mbstring.detect_order = auto
mbstring.http_input = auto
mbstring.http_output = pass

mbstring.substitute_character = none

; String's functions overloading prevents binary use of a string. Use mb_* functions instead
mbstring.func_overload = 0

*/

/* If your server's php.ini is ok, commment this section */

if (get_magic_quotes_gpc())
{
	if (ini_get('magic_quotes_sybase')) { function _q_(&$a) {is_array($a) ? array_walk($a, '_q_') : $a = str_replace("''", "'", $a);} }
	else { function _q_(&$a) {is_array($a) ? array_walk($a, '_q_') : $a = stripslashes($a);} }
	_q_($_GET);_q_($_POST);_q_($_COOKIE);
}

ini_set('log_errors', true);
ini_set('display_errors', false);
ini_set('zlib.output_compression', false);
set_magic_quotes_runtime(false);

/**/


/* Configure your globals CIA settings */

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
