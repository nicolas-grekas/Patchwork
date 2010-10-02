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


abstract class patchwork_tokenizer_bracket extends patchwork_tokenizer
{
	protected

	$onOpenCallbacks = array(),

	$bracketLevel    = 0,
	$bracketPosition = 0,
	$callbacks = array(
		'tagOpen'     => array('('),
		'tagPosition' => ',',
		'tagClose'    => array(')'),
	),
	$parent = 'patchwork_tokenizer';


	// Abstract methods
	function onOpen      (&$token) {}
	function onReposition(&$token) {}
	function onClose     (&$token) {}


	function tagOpen(&$token)
	{
		if (1 === ++$this->bracketLevel)
		{
			if (false === $this->onOpen($token))
			{
				--$this->bracketLevel;
				return false;
			}

			if ($this->onOpenCallbacks)
			{
				$this->register($this->onOpenCallbacks);
				$this->callbacks += $this->onOpenCallbacks;
			}
		}
	}

	function tagPosition(&$token)
	{
		if (1 === $this->bracketLevel)
		{
			++$this->bracketPosition;

			if (false === $this->onReposition($token))
			{
				--$this->bracketPosition;
				return false;
			}
		}
	}

	function tagClose(&$token)
	{
		if (1 === $this->bracketLevel)
		{
			if (false === $this->onClose($token)) return false;
		}

		0 >= --$this->bracketLevel && $this->unregister();
	}
}
