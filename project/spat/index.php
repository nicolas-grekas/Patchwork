<?php @define('CIA', microtime(true)); isset($CONFIG) || $CONFIG = array();

$CONFIG += array(

	'debug' => false,
	'maxage' => 60,

	'DSN' => 'mysqli://spat:spat@localhost/inscriptions',

	'translate_driver' => DEBUG ? 'default_' : 'pearDb',
	'session_driver' => 'file',
	'auth_driver' => 'pearDb',
	'user_driver' => 'pearDb',
);

$path = dirname(__FILE__);
@define('CIA_PROJECT_PATH', $path);
@$include_path .= $path . PATH_SEPARATOR;
@$version_id += filemtime(__FILE__);
require "$path/../../index.php";
