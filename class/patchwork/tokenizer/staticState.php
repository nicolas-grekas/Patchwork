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

	$stateCallbacks = array(
		0 => array(),
		1 => array(
			'tagEOState1' => array(T_WHITESPACE_MULTILINE),
		),
		2 => array(
			'tagEOState2'     => T_COMMENT,
			'tagEOExpression' => array(T_CLOSE_TAG, ';'),
		),
		3 => array(
			'tagEOState3' => T_COMMENT,
		),
	),
	$state = 2,
	$transition = array();


	function __construct(parent $parent = null)
	{
		parent::__construct($parent);

		$this->register($this->stateCallbacks[2]);
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

			echo isset($code[$i][2]) ? $code[$i][2] : '', $code[$i][1];

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

	protected function setState($state)
	{
		$this->transition[count($this->tokens)] = array($state, $this->line);

		if ($this->state === 2) $this->unregister($this->stateCallbacks[1]);
		if ($this->state === $state) return;

		$this->unregister($this->stateCallbacks[$this->state]);
		$this->  register($this->stateCallbacks[$state]);

		$this->state = $state;
	}

	protected function tagEOState2(&$token)
	{
		if ('/*<*/' === $token[1])
		{
			$this->setState(3);
			return false;
		}
		else if ('/**/' === $token[1] && "\n" === substr($token[2], -1))
		{
			$this->setState(1);
			return false;
		}
	}

	protected function tagEOExpression(&$token)
	{
		$this->unregister(array(__FUNCTION__ => $this->stateCallbacks[2][__FUNCTION__]));
		$this->  register($this->stateCallbacks[1]);
	}

	protected function tagEOState1(&$token)
	{
		$this->setState(2);
	}

	protected function tagEOState3(&$token)
	{
		if ('/*>*/' === $token[1])
		{
			$this->setState(2);
			return false;
		}
	}
}
