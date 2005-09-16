<?php @define('CIA', microtime(true)); isset($CONFIG) || $CONFIG = array();

$CONFIG += array(

	'debug' => true,
	'maxage' => 3600,

	'DSN' => 'sqlite://./pineurs.sqlite',
);

$path = dirname(__FILE__);
@define('CIA_PROJECT_PATH', $path);
@$include_path .= $path . PATH_SEPARATOR;
@$version_id += filemtime(__FILE__);
require "$path/../../index.php";
