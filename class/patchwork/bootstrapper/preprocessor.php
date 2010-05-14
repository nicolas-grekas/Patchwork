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


class patchwork_bootstrapper_preprocessor__0
{
	const UTF8_BOM = "\xEF\xBB\xBF";

	static $src;
	public $file, $code;
	protected $callerRx, $alias = array();


	function ob_start($caller)
	{
		$this->callerRx = preg_quote($caller, '/');
		ob_start(array($this, 'ob_eval'));
	}

	function ob_eval($buffer)
	{
		return '' !== $buffer
			? preg_replace('/' . $this->callerRx . '\(\d+\) : eval\(\)\'d code/', $this->file, $buffer)
			: '';
	}

	function staticPass1()
	{
		self::$src = array();

		$code =& $this->code;
		$codeLen = count($code);

		if (!$code) return '';

		$scream = (defined('DEBUG') && DEBUG)
			&& !empty($GLOBALS['CONFIG']['debug.scream'])
				|| (defined('DEBUG_SCREAM') && DEBUG_SCREAM);

		$mode = 2;
		$first_isolation = 0;
		$mode1_transition = false;

		$line = 1;
		$bracket = array();

		$new_code = array();
		$transition = array();
		$error = '';

		for ($i = key($code); $i < $codeLen; ++$i)
		{
			list($type, $token, $line) = $code[$i];

			// Reduce memory usage
			unset($code[$i]);

			if ('' === $error) switch ($type)
			{
			case '{': $bracket[] = '}'; break;
			case '[': $bracket[] = ']'; break;
			case '(': $bracket[] = ')'; break;

			case ')': case ']': case '}':
				if ($token !== $iLast = array_pop($bracket))
				{
					$iLast = $iLast ? ", expecting `{$iLast}'" : '';
					$error = "Patchwork error: Syntax error, unexpected `{$token}'{$iLast} in {$this->file} on line {$line}";
				}
				break;
			}

			switch ($type)
			{
			case '@':
				if ($scream) continue 2;
				break;

			case T_COMMENT:
				if ($mode1_transition && '/**/' === $token)
				{
					$mode1_transition = false;
					if (1 !== $mode)
					{
						$transition[$i] = array($mode = 1, $line);
						$first_isolation = 0;
					}
				}
				else if (2 === $mode && '/*<*/' === $token) $transition[$i] = array($mode = 3, $line);
				else if (3 === $mode && '/*>*/' === $token) $transition[$i] = array($mode = 2, $line);

			case T_WHITESPACE:
				$token = substr_count($token, "\n");
				$token = $token ? str_repeat("\n", $token) : ' ';
				break;

			case T_CLOSE_TAG:
			case ';':
				// This can be broken with specially special multi-line for(;;) statements...
				if (1 < $mode && !$first_isolation) $first_isolation = 1;
				break;

			default:
				if (1 < $mode && 2 == $first_isolation)
				{
					$transition[$i] = array($mode = 2, $line);
					$first_isolation = 3;
				}
			}

			if (T_WHITESPACE === $type && false !== strpos($token, "\n"))
			{
				if (1 < $mode && 1 == $first_isolation) $first_isolation = 2;
				$mode1_transition = true;
			}
			else
			{
				$mode1_transition && 1 === $mode && $transition[$i] = array($mode = 2, $line);
				$mode1_transition = false;
			}

			$new_code[] = $token;
		}

		ob_start();
		echo __CLASS__, '::$src[1]=';

		$iLast = 0;
		$mode = 2;
		$line = '';

		foreach ($transition as $i => $transition)
		{
			$line = implode('', array_slice($new_code, $iLast, $i - $iLast));

			switch ($mode)
			{
			case 1: echo $line; break;
			case 2: var_export($line); break;
			case 3: echo $line, ')."', str_repeat('\n', substr_count($line, "\n")), '"'; break;
			}

			switch ($transition[0])
			{
			case 1: echo 2 === $mode ? ';' : ''; break;
			case 2: echo (3 !== $mode ? (2 === $mode ? ';' : ' ') . __CLASS__ . '::$src[' . $transition[1] . ']=' : '.'); break;
			case 3: echo '.', __CLASS__, '::export('; break;
			}

			$mode = $transition[0];
			$iLast = $i;
		}

		$line = implode('', array_slice($new_code, $iLast));

		switch ($mode)
		{
		case 1: echo $line; break;
		case 2: var_export($line); echo ';'; break;
		case 3: echo $line, ')."', str_repeat('\n', substr_count($line, "\n")), '";'; break;
		}

		if ('' !== $error)
		{
			echo 'echo ';
			var_export($error);
			echo ';';
		}

		return ob_get_clean();
	}

	function staticPass2($token = false)
	{
		if (!self::$src) return '';

		$code = '?'.'>';
		$line = 1;
		foreach (self::$src as $i => $b)
		{
			$code .= str_repeat("\n", $i - $line) . $b;
			$line = $i + substr_count($b, "\n");
		}

		'?'.'><?php' === substr($code, 0, 7) && $code = substr($code, 7);

		self::$src = array();

		if ($this->alias)
		{
			$code = '$patchwork_preprocessor_alias+=' . self::export($this->alias) . ';' . $code;
			$this->alias = array();
		}

		return $code;
	}

	function alias($function, $alias, $args, $return_ref, $marker)
	{
		if (function_exists($function))
		{
			$inline = $function == $alias ? -1 : 2;
			$function = "__patchwork_{$function}";
		}
		else
		{
			$inline = 1;

			if ($function == $alias)
			{
				self::$src[key(self::$src)] .= "die('Patchwork error: Circular aliasing of function {$function}() in ' . __FILE__ . ' on line ' . __LINE__);";
			}
		}

		$args = array($args, array(), array());

		foreach ($args[0] as $k => $v)
		{
			if (is_string($k))
			{
				$k = trim(strtr($k, "\n\r", '  '));
				$args[1][] = $k . '=' . self::export($v);
				0 > $inline && $inline = 0;
			}
			else
			{
				$k = trim(strtr($v, "\n\r", '  '));
				$args[1][] = $k;
			}

			$v = '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*';
			$v = "'^(?:(?:(?: *\\\\ *)?{$v})+(?:&| +&?)|&?) *(\\\${$v})$'D";

			if (!preg_match($v, $k, $v))
			{
				1 !== $inline && $function = substr($function, 12);
				self::$src[key(self::$src)] .= "die('Patchwork error: Invalid parameter for {$function}()\'s alias ({$alias}: {$k}) in ' . __FILE__);";
				return;
			}

			$args[2][] = $v[1];
		}

		$args[1] = implode(',', $args[1]);
		$args[2] = implode(',', $args[2]);

		end(self::$src);

		$inline && $this->alias[1 !== $inline ? substr($function, 12) : $function] = $alias;

		$inline = explode('::', $alias, 2);
		$inline = 2 === count($inline) ? mt_rand(1, mt_getrandmax()) . strtolower($inline[0]) : '';

		self::$src[key(self::$src)] .= $return_ref
			? "function &{$function}({$args[1]}) {/*{$marker}:{$inline}*/\${''}=&{$alias}({$args[2]});return \${''}}"
			: "function  {$function}({$args[1]}) {/*{$marker}:{$inline}*/return {$alias}({$args[2]});}";
	}

	static function export($a, $lf = 0)
	{
		if (is_array($a))
		{
			if ($a)
			{
				$i = 0;
				$b = array();

				foreach ($a as $k => &$a)
				{
					if (is_int($k) && $k >= 0)
					{
						$b[] = ($k !== $i ? $k . '=>' : '') . self::export($a);
						$i = $k+1;
					}
					else
					{
						$b[] = self::export($k) . '=>' . self::export($a);
					}
				}

				$b = 'array(' . implode(',', $b) . ')';
			}
			else return 'array()';
		}
		else if (is_object($a))
		{
			$b = array();
			$v = (array) $a;
			foreach ($v as $k => &$v)
			{
				if ("\0" === substr($k, 0, 1)) $k = substr($k, 3);
				$b[$k] =& $v;
			}

			$b = self::export($b);
			$b = get_class($a) . '::__set_state(' . $b . ')';
		}
		else if (is_string($a) && $a !== strtr($a, "\r\n\0", '---'))
		{
			$b = '"'. str_replace(
				array(  "\\",   '"',   '$',  "\r",  "\n",  "\0"),
				array('\\\\', '\\"', '\\$', '\\r', '\\n', '\\0'),
				$a
			) . '"';
		}
		else $b = is_string($a) ? "'" . str_replace("'", "\\'", str_replace('\\', '\\\\', $a)) . "'" : (
			is_bool($a)   ? ($a ? 'true' : 'false') : (
			is_null($a)   ? 'null' : (
			INF === $a    ? 'INF' : (string) $a
		)));

		$lf && $b .= str_repeat("\n", $lf);

		return $b;
	}
}
