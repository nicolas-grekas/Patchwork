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


// FIXME: handle when $callbackPosition < 0
// TODO: fetch constant callback parameter and use it to do static aliasing
// (See legacy experiment in commit 5779ba)

class patchwork_tokenizer_bracket_callback extends patchwork_tokenizer_bracket
{
	protected $callbackPosition, $lead, $tail;


	function __construct(patchwork_tokenizer $parent, $callbackPosition, $lead, $tail)
	{
		if (0 < $callbackPosition)
		{
			$this->lead = $lead;
			$this->tail = $tail;
			$this->callbackPosition = $callbackPosition - 1;
			$this->initialize($parent);
		}
	}

	function onOpen(&$token)
	{
		if (0 === $this->callbackPosition) $token[1] .= $this->lead;
	}

	function onReposition(&$token)
	{
		     if ($this->bracketPosition === $this->callbackPosition    ) $token[1] .= $this->lead;
		else if ($this->bracketPosition === $this->callbackPosition + 1) $token[1] = $this->tail . $token[1];
	}

	function onClose(&$token)
	{
		if ($this->bracketPosition === $this->callbackPosition) $token[1] = $this->tail . $token[1];
	}


}
