<?php /*********************************************************************
 *
 *   Copyright : (C) 2010 Nicolas Grekas. All rights reserved.
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
	public $file;

	protected

	$callerRx,
	$alias = array(),
	$t;


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
		if ('' === $code = file_get_contents($this->file)) return '';

		$t = new patchwork_tokenizer_normalizer;
		$t = new patchwork_tokenizer_staticState($t);

		if( (defined('DEBUG') && DEBUG)
			&& !empty($GLOBALS['CONFIG']['debug.scream'])
				|| (defined('DEBUG_SCREAM') && DEBUG_SCREAM) )
		{
			new patchwork_tokenizer_scream($t);
		}

		$code = $t->parse($code);
		$code = $t->getStaticCode($code);
		$this->tokenizer = $t;

		if ($t = $t->getErrors())
		{
			$t = $t[0];
			$t = addslashes("{$t[0]} in {$this->file}") . ($t[1] ? " on line {$t[1]}" : '');

			$code .= "die('Patchwork error: {$t}');";
		}

		return $code;
	}

	function staticPass2()
	{
		if (empty($this->tokenizer)) return '';

		$code = $this->alias ? '$patchwork_preprocessor_alias+=' . patchwork_tokenizer::export($this->alias) . ';' : '';
		$code .= substr($this->tokenizer->getRuntimeCode(), 5);
		$this->alias = array();
		$this->tokenizer = null;

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
				return "die('Patchwork error: Circular aliasing of function {$function}() in ' . __FILE__ . ' on line ' . __LINE__);";
			}
		}

		$args = array($args, array(), array());

		foreach ($args[0] as $k => $v)
		{
			if (is_string($k))
			{
				$k = trim(strtr($k, "\n\r", '  '));
				$args[1][] = $k . '=' . patchwork_tokenizer::export($v);
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
				return "die('Patchwork error: Invalid parameter for {$function}()\'s alias ({$alias}: {$k}) in ' . __FILE__);";
			}

			$args[2][] = $v[1];
		}

		$args[1] = implode(',', $args[1]);
		$args[2] = implode(',', $args[2]);

		$inline && $this->alias[1 !== $inline ? substr($function, 12) : $function] = $alias;

		$inline = explode('::', $alias, 2);
		$inline = 2 === count($inline) ? mt_rand(1, mt_getrandmax()) . strtolower($inline[0]) : '';

		// FIXME: when aliasing a user function, this will throw a can not redeclare fatal error!
		// We need some help from the main preprocessor to rename aliased user functions.
		// When done, this will mean that aliasing will be perfect for user function
		// and almost perfect for internal functions: the only uncatchable case would
		// be when using an internal caller (especially objects) with an internal callback.
		// This also means that functions with callback could be untracked,
		// at least when we are sure that an internal function will not be used as a callback.

		return $return_ref
			? "function &{$function}({$args[1]}) {/*{$marker}:{$inline}*/\${''}=&{$alias}({$args[2]});return \${''}}"
			: "function  {$function}({$args[1]}) {/*{$marker}:{$inline}*/return {$alias}({$args[2]});}";
	}
}
