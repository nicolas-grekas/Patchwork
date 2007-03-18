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

; mbstring's functions overloading prevents binary use of strings.
; You should directly use mb_*() functions instead.
mbstring.func_overload = 0
mbstring.substitute_character = "none"

; For performance, uncomment this if all
; your PHP applications can handle UTF-8
;mbstring.language = "uni"
;mbstring.script_encoding = "UTF-8"
;mbstring.encoding_translation = On
;mbstring.http_input = "UTF-8"
;mbstring.http_output = "pass"

*/

$CONFIG += array(

	// password => debug level
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
