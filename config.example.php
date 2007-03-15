<?php

/* php.ini configuration :
 * For maximum performance and security,
 * copy/paste these directives at the end of your php.ini.

magic_quotes_gpc = Off
register_globals = Off
register_long_arrays = Off
register_argc_argv = Off
auto_globals_jit = On

session.auto_start = Off
session.use_only_cookies = On
session.use_cookies = On
session.use_trans_sid = Off

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

	// password => debug level hash
	'DEBUG_PASSWORD' => array('' => 1),

	// Available application's locales
	'lang_list' => 'fr|en',

	// See http://php.net/date_default_timezone_set
	'timezone' => 'Europe/Paris',

	// To send emails. See PEAR's Mail::factory()
	'debug_email' => 'webmaster',
	'email_backend' => 'mail',
	'email_options' => '',

	// Defaults
#	'clientside' => true,
#	'maxage' => 3600,
#	'translate_adapter' => false,
#	'translate_options' => array(),

);
