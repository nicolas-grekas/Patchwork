<?php

foreach (array_keys($GLOBALS) as $k) switch ($k)
{
	# For $_ENV use getenv(), $_REQUEST is banned and the native $_SESSION mecanism is disabled
	case 'k':       case 'CONFIG': case 'GLOBALS':
	case '_SERVER': case '_GET':   case '_POST':
	case '_COOKIE': case '_FILES': break;
	default: unset($GLOBALS[$k]);
}

class debug_CIA extends CIA
{
	private $total_time = 0;
	private $has_error = false;

	public function __construct()
	{
		$this->log('<a href="' . CIA::htmlescape($_SERVER['REQUEST_URI']) . '" target="_blank">' . CIA::htmlescape($_SERVER['REQUEST_URI']) . '</a>');
		register_shutdown_function(array($this, 'log'), '', true);
		parent::__construct();
	}

	public function &ob_handler(&$buffer)
	{
		CIA::$handlesOb = true;
		if (!CIA_BINARY) $buffer = $this->error_end(substr(trim($buffer), 0, 1)) . $buffer;
		return parent::ob_handler($buffer);
	}

	public function error_handler($code, $message, $file, $line, $context)
	{
		if ($code == E_STRICT || !error_reporting()) return;
		$this->has_error = true;
		parent::error_handler($code, $message, $file, $line, $context);
	}

	public function log($message, $is_end = false, $html = true)
	{
		static $prev_time = CIA;
		$this->total_time += $a = 1000*(microtime(true) - $prev_time);


		if ($is_end) $a = sprintf('Total: %.02f ms</pre><pre>', $this->total_time);
		else if (self::$handlesOb) $a = sprintf('%.02f ms: ', $a) . (string) $message . "\n";
		else $a = sprintf('%.02f ms: ', $a) . print_r($message, true) . "\n";

		if (!$html) $a = CIA::htmlescape($a);

		$b = fopen(ini_get('error_log'), 'ab');
		fwrite($b, $a);
		fclose($b);

		$prev_time = microtime(true);
	}

	private function error_end($type)
	{
		$bgcolor = $this->has_error ? 'red' : 'blue';
		$debugWin = CIA_ROOT . '_?d&stop&' . mt_rand();
		$QDebug = CIA::htmlescape(CIA_ROOT . 'js/QDebug.js');

		if ($type=='<') return <<<DEBUG_INFO
<html><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"><script>
_____ = new Date;
onload = function() {
window.debugWin = open('$debugWin','debugWin','dependent=yes,toolbar=no,status=yes,resizable=yes,scrollbars,width=600,height=400');
if (!debugWin) alert('Disable anti-popup to use the Debug Window');
else E('Rendering time: ' + (new Date - _____) + ' ms');
};
</script><div style="position:absolute;top:0px;left:0px;z-index:255;"><a href="javascript:;" onclick="window.debugWin&&debugWin.focus()" style="font-family:arial;font-size:9px;background-color:$bgcolor;color:white;text-decoration:none;border:0px;" id="debugLink">Debug</a>&nbsp<a href="javascript:;" onclick="location.reload(1)" style="font-family:arial;font-size:9px;background-color:$bgcolor;color:white;text-decoration:none;border:0px;">Reload</a><script src="$QDebug"></script></div>

DEBUG_INFO;
		else if ($type=='w' && $this->has_error) return "L=document.getElementById('debugLink'); L && (L.style.backgroundColor='$bgcolor');";
	}
}
