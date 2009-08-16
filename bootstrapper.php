<?php /*********************************************************************
 *
 *   Copyright : (C) 2007 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


isset($_GET['p:']) && 'exit' === $_GET['p:'] && die('Exit requested');

error_reporting(E_ALL | E_STRICT);
@ini_set('display_errors', true); // Only while bootstrapping


// Mandatory PHP dependencies

if (!function_exists('version_compare') || version_compare(phpversion(), '5.1.4', '<'))
{
	die("PHP 5.1.4 or higher is required.");
}
function_exists('token_get_all') || die('Patchwork Error: Extension "tokenizer" is needed and not loaded');
preg_match('/^.$/u', '§')        || die('Patchwork Error: PCRE is not compiled with UTF-8 support');
isset($_SERVER['REDIRECT_STATUS']) && '200' !== $_SERVER['REDIRECT_STATUS'] && die('Patchwork Error: initialization forbidden (try using the shortest possible URL)');

require dirname(__FILE__) . '/class/patchwork/bootstrapper/preprocessor.php';
require dirname(__FILE__) . '/class/patchwork/bootstrapper.php';

patchwork_bootstrapper::$file = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'common.php';

if (file_exists(patchwork_bootstrapper::$file))
{
	patchwork_bootstrapper::preprocessor_ob_start(__FILE__);
	eval(patchwork_bootstrapper::preprocessorPass1());
	eval(patchwork_bootstrapper::preprocessorPass2(true));
	patchwork_bootstrapper::release();
}

if (extension_loaded('mbstring'))
{
	(@ini_get('mbstring.func_overload') & MB_OVERLOAD_STRING)
		&& die('Patchwork Error: mbstring is overloading string functions');

	ini_get_bool('mbstring.encoding_translation')
		&& !in_array(strtolower(ini_get('mbstring.http_input')), array('pass', 'utf-8'))
		&& die('Patchwork Error: mbstring is set to translate input encoding');
}


// Start bootstrapping

if (!patchwork_bootstrapper::getLock(__FILE__))
{
	require patchwork_bootstrapper::$cwd . '.patchwork.php';
	return;
}

require dirname(__FILE__) . '/class/patchwork/bootstrapper/inheritance.php';
require dirname(__FILE__) . '/class/patchwork/bootstrapper/updatedb.php';

$patchwork_path = patchwork_bootstrapper::getPath();
patchwork_bootstrapper::initZcache();
patchwork_bootstrapper::preprocessor_ob_start(__FILE__);


// Load preconfig

foreach (patchwork_bootstrapper::$rSlice as patchwork_bootstrapper::$file)
{
	patchwork_bootstrapper::$file .= 'preconfig.php';

	if (file_exists(patchwork_bootstrapper::$file))
	{
		eval(patchwork_bootstrapper::preprocessorPass1());
		eval(patchwork_bootstrapper::preprocessorPass2(true));
		ob_get_length() && ob_flush();
	}
}


// Purge old sources cache and set $token

patchwork_bootstrapper::afterPreconfig();


// Load config

foreach (patchwork_bootstrapper::$fSlice as patchwork_bootstrapper::$file)
{
	patchwork_bootstrapper::$file .= 'config.patchwork.php';

	if (isset(patchwork_bootstrapper::$configSource[patchwork_bootstrapper::$file]))
	{
		patchwork_bootstrapper::$configCode[patchwork_bootstrapper::$file] =& patchwork_bootstrapper::$configSource[patchwork_bootstrapper::$file];
		eval(patchwork_bootstrapper::$configSource[patchwork_bootstrapper::$file]);
		ob_get_length() && ob_flush();
	}
}


// Load postconfig

foreach (patchwork_bootstrapper::$rSlice as patchwork_bootstrapper::$file)
{
	patchwork_bootstrapper::$file .= 'postconfig.php';

	if (file_exists(patchwork_bootstrapper::$file))
	{
		eval(patchwork_bootstrapper::preprocessorPass1());
		eval(patchwork_bootstrapper::preprocessorPass2());
		ob_get_length() && ob_flush();
	}
}


ob_end_flush();


// Setup hook

if (!ob_get_length())
{
	class p extends patchwork {}
	patchwork_setup::hook();
}


// Save config and release lock

patchwork_bootstrapper::release();


// Let's go

patchwork::start();
