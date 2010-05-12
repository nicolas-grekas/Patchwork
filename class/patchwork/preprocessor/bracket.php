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


class
{
	protected

	$preproc,
	$registered = false,
	$first,
	$position,
	$bracket;


	function __construct($preproc)
	{
		$this->preproc = $preproc;
		$this->setupFilter();
	}

	function setupFilter()
	{
		$this->popFilter();
		$this->preproc->pushFilter(array($this, 'filterToken'));
		$this->first = $this->registered = true;
		$this->position = 0;
		$this->bracket = 0;
	}

	function popFilter()
	{
		$this->registered && $this->preproc->popFilter();
		$this->registered = false;
	}

	function filterPreBracket($type, $token)
	{
		0>=$this->bracket
			&& T_WHITESPACE != $type && T_COMMENT != $type && T_DOC_COMMENT != $type
			&& $this->popFilter();
		return $token;
	}

	function filterBracket($type, $token) {return $token;}
	function onStart      ($token) {return $token;}
	function onReposition ($token) {return $token;}
	function onClose      ($token) {$this->popFilter(); return $token;}

	function filterToken($type, $token)
	{
		if ($this->first) $this->first = false;
		else switch ($type)
		{
		case '(':
			$token = 1 < ++$this->bracket
				? $this->filterBracket($type, $token)
				: $this->onStart($token);
			break;

		case ')':
			$token = --$this->bracket
				? (
					0 > $this->bracket
					? $this->filterPreBracket($type, $token)
					: $this->filterBracket($type, $token)
				)
				: $this->onClose($token);
			break;

		case ',':
			if (1 === $this->bracket)
			{
				$token = $this->filterBracket($type, $token);
				++$this->position;
				$token = $this->onReposition($token);
				break;
			}

		default:
			$token = 0 < $this->bracket
				? $this->filterBracket($type, $token)
				: $this->filterPreBracket($type, $token);
		}

		return $token;
	}
}
