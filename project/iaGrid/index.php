<?php defined('CIA') || define('CIA', microtime(true)) && $CONFIG = array();

$CONFIG += array(
	'DSN' => 'mysql://iaCalc@localhost/iaCalc',
);

$p = dirname(__FILE__);
defined('CIA_PROJECT_PATH') || define('CIA_PROJECT_PATH', $p) && ($cia_paths = array()) || ($version_id = 0);
$version_id += filemtime(__FILE__);
require ($cia_paths[] = $p) . '/../../index.php';
