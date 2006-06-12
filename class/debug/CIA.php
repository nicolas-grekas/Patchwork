<?php

foreach (array_keys($GLOBALS) as $k) switch ($k)
{
	# $_REQUEST is banned
	case 'cia_paths': case 'k':
	case '_SESSION': case 'CONFIG': case 'GLOBALS':
	case '_SERVER':  case '_GET':   case '_POST':
	case '_COOKIE':  case '_FILES': case '_ENV': break;
	default: unset($GLOBALS[$k]);
}

if (isset($_SERVER['PHP_AUTH_USER']))
{
	$_SERVER['PHP_AUTH_USER'] = $_SERVER['PHP_AUTH_PW'] = "Don't use me, it would be a security hole (cross site javascript).";
}

class debug_CIA extends CIA
{
	private $total_time = 0;

	public function __construct()
	{
		$this->log(
			'<a href="' . htmlspecialchars($_SERVER['REQUEST_URI']) . '" target="_blank">'
			. htmlspecialchars(preg_replace("'&v\\\$=[^&]*'", '', $_SERVER['REQUEST_URI']))
			. '</a>'
		);
		register_shutdown_function(array($this, 'log'), '', true);
		parent::__construct();
	}

	public function &ob_handler(&$buffer)
	{
		self::$handlesOb = true;
		if (false !== stripos(self::$headers['content-type'], 'text/html')) $buffer = $this->error_end(substr(trim($buffer), 0, 1)) . $buffer;
		return parent::ob_handler($buffer);
	}

	public function log($message, $is_end = false, $html = true)
	{
		static $prev_time = CIA;
		$this->total_time += $a = 1000*(microtime(true) - $prev_time);

		if ('__getDeltaMicrotime' !== $message)
		{
			if ($is_end) $a = sprintf('Total: %.02f ms</pre><pre>', $this->total_time);
			else if (self::$handlesOb) $a = sprintf('%.02f ms: ', $a) . serialize($message) . "\n";
			else $a = sprintf('%.02f ms: ', $a) . print_r($message, true) . "\n";

			if (!$html) $a = htmlspecialchars($a);

			$b = ini_get('error_log');
			$b = fopen($b ? $b : './error.log', 'ab');
			flock($b, LOCK_EX);
			fseek($b, 0, SEEK_END);
			fwrite($b, $a, strlen($a));
			fclose($b);
		}

		$prev_time = microtime(true);

		return $a;
	}

	private function error_end($type)
	{
		$bgcolor = $this->has_error ? 'red' : 'blue';
		$debugWin = self::$home . '_?d&stop&' . mt_rand();
		$QDebug = self::$home . 'js/QDebug.js';
		$lang = CIA::__LANG__();

		if ($type=='<') return <<<EOHTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<script type="text/javascript">/*<![CDATA[*/
_____ = new Date/1;
onload = function() {
window.debugWin = open('$debugWin','debugWin','dependent=yes,toolbar=no,status=yes,resizable=yes,scrollbars,width=320,height=240,left=' + parseInt(screen.availWidth - 340) + ',top=' + parseInt(screen.availHeight - 290));
if (!debugWin) alert('Disable anti-popup to use the Debug Window');
else E('Rendering time: ' + (new Date/1 - _____) + ' ms');
};
/*]]>*/</script>
<div style="position:fixed;_position:absolute;float:right;font-family:arial;font-size:9px;top:0px;right:0px;z-index:255"><a href="javascript:;" onclick="window.debugWin&&debugWin.focus()" style="background-color:$bgcolor;color:white;text-decoration:none;border:0px;" id="debugLink">Debug</a>&nbsp<a href="javascript:;" onclick="location.reload(1)" style="background-color:$bgcolor;color:white;text-decoration:none;border:0px;">Reload</a><script type="text/javascript" src="$QDebug"></script></div>

EOHTML;

		else if ($type=='w' && $this->has_error) return "L=document.getElementById('debugLink'); L && (L.style.backgroundColor='$bgcolor');";
	}
}
