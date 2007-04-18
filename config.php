<?php


// Import all the pieces of the patchwork.
// If you want more granularity, set the directive to your exact needs.

#import pieces


/* All default settings */

$CONFIG += array(

	// Debug features
#	'DEBUG_ALLOWED'  => 1,
#	'DEBUG_PASSWORD' => '',

	// Enable browser-side page rendering when available ?
#	'clientside' => true,

	// Max age (in seconds) for HTTP ressources caching
#	'maxage' => 2678400, // 31d x 24h x 3600s ~ 1 month

	// Session cookie
#	session.cookie_path => '/',
#	session.cookie_domain => '',

	// P3P - Platform for Privacy Preferences
#	'P3P => 'CUR ADM',

	// Translation tables adapter config.
#	'translate_adapter' => false,
#	'translate_options' => array(),

);
