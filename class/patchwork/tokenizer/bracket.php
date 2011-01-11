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

	$bracketLevel = 0,
	$bracketIndex = 0,
	$callbacks = array(
		'tagOpen'  => '(',
		'tagIndex' => ',',
		'tagClose' => ')',
	);


	// "Abstract" methods
	protected function onOpen      (&$token) {}
	protected function onReposition(&$token) {}
	protected function onClose     (&$token) {}


	protected function tagOpen(&$token)
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

	protected function tagIndex(&$token)
	{
		if (1 === $this->bracketLevel)
		{
			++$this->bracketIndex;

			if (false === $this->onReposition($token))
			{
				--$this->bracketIndex;
				return false;
			}
		}
	}

	protected function tagClose(&$token)
	{
		if (1 === $this->bracketLevel)
		{
			if (false === $this->onClose($token)) return false;
		}

		0 >= --$this->bracketLevel && $this->unregister();
	}
}
