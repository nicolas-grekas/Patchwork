<?php


// Import basic pieces of patchwork.

#import pieces/iaForm/pieces/*
#import pieces/lingua
#import pieces/iaMail
#import pieces/toolbox
#import pieces/ie7


/* All default settings */

$CONFIG += array(

	// Debug features
#	'DEBUG_ALLOWED'  => true,
#	'DEBUG_PASSWORD' => '',

	// List of available languages ("en|fr" for example).
#	'lang_list' => '',

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
