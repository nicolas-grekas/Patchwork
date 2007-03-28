<?php

/* php.ini configuration :
 * Not mandatory, but for maximum performance and security,
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



/* All default settings */

$CONFIG += array(

	// Debug features
#	'DEBUG_ALLOWED'  => 1,
#	'DEBUG_PASSWORD' => '',

	// Used by iaMail in test mode
#	'debug_email' => 'webmaster',

	// Defaults for PEAR's Mail_mime
#	'email_backend' => 'mail',
#	'email_options' => '',


	// Enable browser-side page rendering when available ?
#	'clientside' => true,

	// Max age (in seconds) for HTTP ressources caching
#	'maxage' => 3600,

	// Session cookie
#	session.cookie_path => '/',
#	session.cookie_domain => '',

	// P3P - Platform for Privacy Preferences
#	'P3P => 'CUR ADM',

	// Translation tables adapter config.
#	'translate_adapter' => false,
#	'translate_options' => array(),

);
