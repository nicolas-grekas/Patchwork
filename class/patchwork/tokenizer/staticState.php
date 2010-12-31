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


class patchwork_tokenizer_staticState extends patchwork_tokenizer
{
	protected

	$bracket = array(),
	$callbacks = array(
		'pushBracket' => array('{', '[', '('),
		'popBracket'  => array('}', ']', ')'),
	),
	$stateCallbacks = array(
		1 => array(
			'tagEOState1'  => T_COMMENT,
			'tagEOState1b' => array(T_MULTILINE_SUGAR => T_WHITESPACE),
		),
		2 => array(
			'tagEOState2'     => T_COMMENT,
			'tagEOExpression' => array(T_CLOSE_TAG, ';'),
		),
		3 => array(
			'tagEOState3' => T_COMMENT,
		),
		4 => array(
			'tagEOState4' => T_COMMENT,
		),
	),
	$state = 2,
	$transition = array(),
	$nextState,
	$runtimeKey;

	static $runtimeCode = array();


	function __construct(parent $parent = null)
	{
		parent::__construct($parent);
		$this->register($this->stateCallbacks[2]);
		$this->runtimeKey = mt_rand(1, mt_getrandmax());
	}

	function getStaticCode($code)
	{
		$var = '$§' . $this->runtimeKey;

		$O = $this->transition ? end($this->transition) : array(1 => 1);
		$O = "{$var}=&" . __CLASS__ . "::\$runtimeCode[{$this->runtimeKey}];{$var}=array(array(1,(";

		$state = 2;
		$o = '';
		$j = 0;

		foreach ($this->transition as $i => $transition)
		{
			do
			{
				$o .= $code[$j];
				unset($code[$j]);
			}
			while (++$j < $i);

			$O .= (2 === $state ? self::export($o) . str_repeat("\n", substr_count($o, "\n")) : $o)
				. (1 !== $state ? ')))' . (3 !== $state ? ';' : '') : '');

			if (1 !== $transition[0])
			{
				$O .= "({$var}[]=array({$transition[1]},"
					. (4 === $transition[0] ? 'patchwork_tokenizer::export(' : '(');
			}

			$state = $transition[0];
			$o = '';
		}

		$this->transition = array();
		$o = implode('', $code);

		return $O
			. (2 === $state ? self::export($o) . str_repeat("\n", substr_count($o, "\n")) : $o)
			. (1 !== $state ? ')))' . (3 !== $state ? ';' : '') : '')
			. "unset({$var});";
	}

	function getRuntimeCode()
	{
		$code =& self::$runtimeCode[$this->runtimeKey];

		if (empty($code)) return '';

		$line = 1;

		foreach ($code as $k => &$v)
		{
			$v[1] = str_repeat("\n", $v[0] - $line) . $v[1];
			$line += substr_count($v[1], "\n");
			$v = $v[1];
		}

		$code = implode('', $code);
		unset(self::$runtimeCode[$this->runtimeKey]);

		return $code;
	}

	function pushBracket(&$token)
	{
		$s = empty($this->nextState) ? $this->state : $this->nextState;

		switch ($token[0])
		{
		case '{': $this->bracket[] = array($s, '}'); break;
		case '[': $this->bracket[] = array($s, ']'); break;
		case '(': $this->bracket[] = array($s, ')'); break;
		}
	}

	function popBracket(&$token)
	{
		$s = empty($this->nextState) ? $this->state : $this->nextState;

		if (array($s, $token[0]) !== $last = array_pop($this->bracket))
		{
			$this->unregister();

			$last = $last && $s === $last[0] ? ", expecting `{$last[1]}'" : '';

			$this->setError("Syntax error, unexpected `{$token[0]}'{$last}");
		}
	}

	function setState($state, &$token = array(0, ''))
	{
		empty($this->nextState) && $this->register('tagTransition');
		$this->nextState = $state;

		if (2 !== $state || 2 < $this->state) $this->tagTransition($token);

		if ($this->state === 2) $this->unregister($this->stateCallbacks[1]);
		if ($this->state === $state) return false;

		$this->unregister($this->stateCallbacks[$this->state]);
		$this->  register($this->stateCallbacks[$state]);

		$this->state = $state;

		return false;
	}

	function tagTransition(&$token)
	{
		$this->unregister(__FUNCTION__);
		end($this->code);
		$this->transition[key($this->code)+1] = array($this->nextState, $this->line + substr_count($token[1], "\n"));
		unset($this->nextState);
	}

	function tagEOState2(&$token)
	{
		if ('/*<*/' === $token[1])
		{
			return $this->setState(4);
		}
		else if ('/**/' === $token[1] && "\n" === substr(end($this->code), -1))
		{
			return $this->setState(1);
		}
	}

	function tagEOExpression(&$token)
	{
		$this->unregister(array(__FUNCTION__ => $this->stateCallbacks[2][__FUNCTION__]));
		$this->  register($this->stateCallbacks[1]);
	}

	function tagEOState1(&$token)
	{
		if ('/*<*/' === $token[1]) return $this->setState(3);

		"\n" === substr($token[1], -1) && $this->setState(2, $token);
	}

	function tagEOState1b(&$token)
	{
		$this->setState(2, $token);
	}

	function tagEOState3(&$token)
	{
		if ('/*>*/' === $token[1]) return $this->setState(1);
	}

	function tagEOState4(&$token)
	{
		if ('/*>*/' === $token[1]) return $this->setState(2);
	}
}
