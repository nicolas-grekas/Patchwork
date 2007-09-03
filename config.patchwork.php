<?php



/**** Configuration stage 1 ****/



// Import basic pieces of patchwork.

#patchwork pieces/pForm/pieces/*
#patchwork pieces/lingua
#patchwork pieces/pMail
#patchwork pieces/toolbox
#patchwork pieces/ie7


/* All default settings */

$CONFIG += array(

	// General
#	'clientside'     => true,      // Enable browser-side page rendering when available
#	'i18n.lang_list' => '',        // List of available languages ('en|fr' for example)
#	'maxage'         => 2678400,   // Max age (in seconds) for HTTP ressources caching
#	'P3P             => 'CUR ADM', // P3P - Platform for Privacy Preferences
#	'xsendfile'      => false,     // If your server is "X-Sendfile" enabled, turn this to true

	// Session
#	'session.save_path'     => PATCHWORK_ZCACHE,
#	'session.cookie_path'   => '/',
#	'session.cookie_domain' => '',
#	'session.auth_vars' => array(), // Set of session vars used for authentication or authorization
#	'session.group_vars' => array(), // Set of session vars whose values define user groups

	// Translation adapter
#	'translate.adapter' => false,
#	'translate.options' => array(),

);



/**** Configuration stage 0 ****/



$CONFIG += array(

	// Debug features
#	'debug.allowed'  => true,
#	'debug.password' => '',
#	'turbo' => false,		// Run patchwork at full speed, at the cost of source code desynchronisation
#	'umask' => umask(),		// Set the user file creation mode mask

);
