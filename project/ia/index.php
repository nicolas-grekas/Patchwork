<?php @define('CIA', microtime(true)); isset($CONFIG) || $CONFIG = array();

$CONFIG += array(

	'debug' => 1,
	'maxage' => 60,

);

$path = dirname(__FILE__);
@define('CIA_PROJECT_PATH', $path);
@$include_path .= $path . PATH_SEPARATOR;
@$version_id += filemtime(__FILE__);
require "$path/../../index.php";
