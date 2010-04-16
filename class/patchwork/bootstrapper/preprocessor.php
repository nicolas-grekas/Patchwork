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

	static $code;
	public $file;
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
		$scream = (defined('DEBUG') && DEBUG)
			&& !empty($GLOBALS['CONFIG']['debug.scream'])
				|| (defined('DEBUG_SCREAM') && DEBUG_SCREAM);

		$code = file_get_contents($this->file);
		self::UTF8_BOM === substr($code, 0, 3) && $code = substr($code, 3);
		false !== strpos($code, "\r") && $code = strtr(str_replace("\r\n", "\n", $code), "\r", "\n");
		$code = preg_replace('/\?>$/', ';', $code);

		$code = token_get_all($code);
		$codeLen = count($code);

		$mode = 2;
		$first_isolation = 0;
		$mode1_transition = false;

		$line = 1;
		$bracket = array();

		$new_code = array();
		$transition = array();

		for ($i = 0; $i < $codeLen; ++$i)
		{
			if (is_array($code[$i]))
			{
				$type  = $code[$i][0];
				$token = $code[$i][1];
			}
			else
			{
				$token = $code[$i];
				$type  = $token;
			}

			// Reduce memory usage
			unset($code[$i]);

			switch ($type)
			{
			case T_CURLY_OPEN:
			case T_DOLLAR_OPEN_CURLY_BRACES:
			case '{': $bracket[] = '}'; break;
			case '[': $bracket[] = ']'; break;
			case '(': $bracket[] = ')'; break;

			case ')': case ']': case '}':
				if ($token !== $iLast = array_pop($bracket))
				{
					$iLast = $iLast ? ", expecting `{$iLast}'" : '';
					die("Parse error: syntax error, unexpected `{$token}'{$iLast} in {$this->file} on line {$line}");
				}
				break;
			}

			switch ($type)
			{
			case '@':
				if ($scream)
				{
					$code[$i--] = array(T_WHITESPACE, ' ');
					continue 2;
				}
				break;

			case T_OPEN_TAG:
				$token = '<?php ' . str_repeat("\n", substr_count($token, "\n"));
				break;

			case T_DOC_COMMENT:
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
			$line += substr_count($token, "\n");
		}

		$code = '';
		ob_start();
		echo __CLASS__, '::$code[1]=';

		$iLast = 0;
		$mode = 2;
		$line = '';

		foreach ($transition as $i => $transition)
		{
			$line = implode('', array_slice($new_code, $iLast, $i - $iLast));

			switch ($mode)
			{
			case 1: echo $line; break;
			case 2: echo var_export($line, true); break;
			case 3: echo $line, ')."', str_repeat('\n', substr_count($line, "\n")), '"'; break;
			}

			switch ($transition[0])
			{
			case 1: echo 2 === $mode ? ';' : ''; break;
			case 2: echo (3 !== $mode ? (2 === $mode ? ';' : ' ') . __CLASS__ . '::$code[' . $transition[1] . ']=' : '.'); break;
			case 3: echo '.', __CLASS__, '::export('; break;
			}

			$mode = $transition[0];
			$iLast = $i;
		}

		$line = implode('', array_slice($new_code, $iLast));

		switch ($mode)
		{
		case 1: echo $line; break;
		case 2: echo var_export($line, true), ';'; break;
		case 3: echo $line, ')."', str_repeat('\n', substr_count($line, "\n")), '";'; break;
		}

		$code = ob_get_clean();
		self::$code = array();

		return $code;
	}

	function staticPass2($token = false)
	{
		$code = '?>';
		$line = 1;
		foreach (self::$code as $i => $b)
		{
			$code .= str_repeat("\n", $i - $line) . $b;
			$line = $i + substr_count($b, "\n");
		}

		'?><?php' === substr($code, 0, 7) && $code = substr($code, 7);

		self::$code = array();

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
		else if ($function == $alias) die("Patchwork Error: circular aliasing of {$alias}");
		else $inline = 1;

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

			$args[2][] = '&' === substr($k, 0, 1) ? substr($k, 1) : $k;
		}

		$args[1] = implode(',', $args[1]);
		$args[2] = implode(',', $args[2]);

		end(self::$code);

		$inline && $this->alias[1 !== $inline ? substr($function, 12) : $function] = $alias;

		$inline = explode('::', $alias, 2);
		$inline = 2 === count($inline) ? mt_rand(1, mt_getrandmax()) . strtolower($inline[0]) : '';

		//XXX bug when aliasing a user function, this will throw a can not redeclare fatal error!
		// We need some help from the main preprocessor to rename aliased user functions.
		// When done, this will mean that aliasing will be perfect for user function
		// and almost perfect for internal functions: the only uncatchable case would
		// be when using an internal caller (especially objects) with an internal callback.
		// This also means that functions aliased to catch their callback could be un aliased,
		// at least when we are sure that an internal function can not be used as a callback.

		self::$code[key(self::$code)] .= $return_ref
			? "function &{$function}({$args[1]}) {/*{$marker}:{$inline}*/\${''}=&{$alias}({$args[2]});return \${''}}"
			: "function  {$function}({$args[1]}) {/*{$marker}:{$inline}*/return {$alias}({$args[2]});}";
	}

	static function export($a, $lf = 0)
	{
		if (is_array($a))
		{
			if ($a)
			{
				$b = array();
				foreach ($a as $k => &$a) $b[] = self::export($k) . '=>' . self::export($a);
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
			is_null($a)   ? 'null' : (string) $a
		));

		$lf && $b .= str_repeat("\n", $lf);

		return $b;
	}
}
