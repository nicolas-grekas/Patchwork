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

	$callbacks = array(
		0 => array(),
		1 => array(
			'tagEOState1' => array("\n" => T_WHITESPACE),
		),
		2 => array(
			'tagEOState2'     => array('/*<*/' => T_COMMENT, '/**/' => T_COMMENT),
			'tagEOExpression' => array(T_CLOSE_TAG, ';'),
		),
		3 => array(
			'tagEOState3' => array('/*>*/' => T_COMMENT),
		),
	),
	$state = 0,
	$transition;


	function __construct(patchwork_tokenizer &$parent = null)
	{
		parent::__construct($parent);

		$this->state = 0;
		$this->transition = array();
		$this->setState(2, 1, $parent);
	}

	function getStaticCode($code, $class)
	{
		$code   = $this->tokenize($code);
		$length = count($code);
		$state  = 2;

		ob_start();
		echo $class, '::$src[1]=';

		ob_start();

		for ($i = 0; $i < $length; ++$i)
		{
			if (isset($this->transition[$i]))
			{
				switch ($state)
				{
				case 1: ob_end_flush(); break;
				case 2: var_export(ob_get_clean()); break;
				case 3: echo ')."', str_repeat('\n', substr_count(ob_get_flush(), "\n")), '"';
				}

				switch ($this->transition[$i][0])
				{
				case 1: echo 2 === $state ? ';' : ''; break;
				case 2: echo 3 !== $state ? (2 === $state ? ';' : ' ') . $class . '::$src[' . $this->transition[$i][1] . ']=' : '.'; break;
				case 3: echo '.patchwork_tokenizer::export('; break;
				}

				$state = $this->transition[$i][0];
				unset($this->transition[$i]);

				ob_start();
			}

			echo isset($code[$i][3]) ? $code[$i][3] : '', $code[$i][1];

			unset($code[$i]);
		}

		switch ($state)
		{
		case 1: ob_end_flush(); break;
		case 2: var_export(ob_get_clean()); echo ';'; break;
		case 3: echo ')."', str_repeat('\n', substr_count(ob_get_flush(), "\n")), '";'; break;
		}

		return ob_get_clean();
	}

	protected function setState($state, $line, $t)
	{
		$this->transition[count($t->tokens)] = array($state, $line);

		if ($this->state === 2) $t->unregister($this, $this->callbacks[1]);
		if ($this->state === $state) return;

		$t->unregister($this, $this->callbacks[$this->state]);
		$t->  register($this, $this->callbacks[$state]);

		$this->state = $state;
	}

	function tagEOState2($token, $t)
	{
		if ('/*<*/' === $token[1])
		{
			$this->setState(3, $token[2], $t);
		}
		else if ('/**/' === $token[1] && "\n" === substr($token[3], -1))
		{
			$this->setState(1, $token[2], $t);
		}
	}

	function tagEOExpression($token, $t)
	{
		$t->unregister($this, array(__FUNCTION__ => $this->callbacks[2][__FUNCTION__]));
		$t->  register($this, $this->callbacks[1]);
	}

	function tagEOState1($token, $t)
	{
		if (false !== strpos($token[1], "\n"))
		{
			$this->setState(2, $token[2], $t);
		}
	}

	function tagEOState3($token, $t)
	{
		if ('/*>*/' === $token[1])
		{
			$this->setState(2, $token[2], $t);
		}
	}
}
