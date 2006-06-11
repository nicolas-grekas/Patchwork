<?php defined('CIA') || define('CIA', microtime(true)) && $CONFIG = array();

$CONFIG += array(

	'maxage' => 60,

	'DSN' => 'mysqli://spat:spat@localhost/inscriptions',

	'translate_driver' => (int) @$_COOKIE['DEBUG'] ? 'default_' : 'pearDb',
	'session_driver' => 'file',
	'auth_driver' => 'pearDb',
	'user_driver' => 'pearDb',
);

$p = dirname(__FILE__);
defined('CIA_PROJECT_PATH') || define('CIA_PROJECT_PATH', $p) && $cia_paths = array() || $version_id = 0;
$version_id += filemtime(__FILE__);
require ($cia_paths[] = $p) . '/../../index.php';
