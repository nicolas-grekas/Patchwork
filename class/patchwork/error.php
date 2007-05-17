<?php /*********************************************************************
 *
 *   Copyright : (C) 2006 Nicolas Grekas. All rights reserved.
 *   Email     : nicolas.grekas+patchwork@espci.org
 *   License   : http://www.gnu.org/licenses/gpl.txt GNU/GPL, see COPYING
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/


class extends patchwork
{
	static function call($code, $message, $file, $line, &$context)
	{
		patchwork::setMaxage(0);
		patchwork::setExpires('onmaxage');
		patchwork::$private = true;

		if (!function_exists('filterErrorArgs'))
		{
			function filterErrorArgs($a, $k = true)
			{
				switch (gettype($a))
				{
					case 'object': return '(object) ' . get_class($a);

					case 'array':
						if ($k)
						{
							$b = array();

							foreach ($a as $k => &$v) $b[$k] = filterErrorArgs($v, false);
						}
						else $b = 'array(...)';

						return $b;

					case 'string': return '(string) ' . $a;

					case 'boolean': return $a ? 'true' : 'false';
				}

				return $a;
			}
		}

		$context = '';

		if (!patchwork::$handlesOb)
		{
			$msg = debug_backtrace();

			$context = array();
			$i = 0;
			$length = count($msg);
			while ($i < $length)
			{
				$a = array(
					' in   ' => @ "{$msg[$i]['file']} line {$msg[$i]['line']}",
					' call ' => @ (isset($msg[$i]['class']) ? $msg[$i]['class'].$msg[$i]['type'] : '') . $msg[$i]['function'] . '()'
				);

				if (
					in_array(
						$a[' call '],
						array(
							'patchwork->error_handler()',
							'require()', 'require_once()',
							'include()', 'include_once()',
						)
					)
				)
				{
					++$i;
					continue;
				}

				if (isset($msg[$i]['args']) && $msg[$i]['args']) $a[' args '] = array_map('filterErrorArgs', $msg[$i]['args']);

				$context[$i++] = $a;
			}

			$context = htmlspecialchars( print_r($context, true) );
		}

		switch ($code)
		{
		case E_ERROR:             $msg = '<b>Fatal Error</b>';             break;
		case E_USER_ERROR:        $msg = '<b>Fatal User Error</b>';        break;
		case E_RECOVERABLE_ERROR: $msg = '<b>Fatal Recoverable Error</b>'; break;
			
		case E_WARNING:      $msg = '<b>Warning</b>';       break;
		case E_USER_WARNING: $msg = '<b>User Warning</b>';  break;
		case E_NOTICE:       $msg = '<b>Notice</b>';        break;
		case E_USER_NOTICE:  $msg = '<b>User Notice</b>';   break;
		case E_STRICT:       $msg = '<b>Strict Notice</b>'; break;
		default:             $msg = '<b>Unknown Error (#'.$code.')</b>';
		}

		$cid = patchwork::uniqid();
		$cid = <<<EOHTML
<script type="text/javascript">/*<![CDATA[*/
focus()
L=opener&&opener.document.getElementById('debugLink')
L=L&&L.style
if(L)
{
L.backgroundColor='red'
L.fontSize='18px'
}
//]]></script><a href="javascript:;" onclick="var a=document.getElementById('{$cid}');a.style.display=a.style.display?'':'none';" style="color:red;font-weight:bold">{$msg}</a>
in <b>$file</b> line <b>$line</b>:\n{$message}<blockquote id="{$cid}" style="display:none">Context: {$context}</blockquote><br><br>
EOHTML;

		$i = ini_get('error_log');
		$i = fopen($i ? $i : './error.log', 'ab');
		flock($i, LOCK_EX);
		fwrite($i, $cid);
		fclose($i);

		switch ($code)
		{
		case E_ERROR:
		case E_USER_ERROR:
		case E_RECOVERABLE_ERROR:
			exit;
		}
	}
}
