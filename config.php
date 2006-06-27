<?php defined('CIA') || define('CIA', microtime(true)) && $CONFIG = array();

$CONFIG += array(
	'timezone' => 'Europe/Paris',
	'php' => 'c:/progra~1/wamp/php/php.exe', // Path to your php executable.

	'maxage' => 3600,
	'lang_list' => 'fr',

	'allow_debug' => true,

	'translate_driver' => 'default_',
	'translate_params' => array(),
);


/* Config initialisation */

@putenv('LC_ALL=en_US.UTF-8');
setlocale(LC_ALL, 'en_US.UTF-8');
if (function_exists('iconv_set_encoding'))
{
	iconv_set_encoding('input_encoding', 'UTF-8');
	iconv_set_encoding('internal_encoding', 'UTF-8');
	iconv_set_encoding('output_encoding', 'UTF-8');
}

$p = dirname(__FILE__);
defined('CIA_PROJECT_PATH') || define('CIA_PROJECT_PATH', $p) && $cia_paths = array() || $version_id = 0;
$version_id += filemtime(__FILE__);
require ($cia_paths[] = $p) . '/bootstrap.php';
