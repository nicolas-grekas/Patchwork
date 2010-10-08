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
// TODO: enable class aliasing by detecting array('classname', ...)
// TODO: fetch constant callback parameter and use it to do static aliasing
// (See legacy experiment in commit 5779ba)

class patchwork_tokenizer_bracket_callback extends patchwork_tokenizer_bracket
{
	protected $callbackPosition;


	function __construct(patchwork_tokenizer $parent, $callbackPosition)
	{
		if (0 < $callbackPosition)
		{
			$this->callbackPosition = $callbackPosition - 1;
			$this->initialize($parent);
		}
	}

	function onOpen(&$token)
	{
		if (0 === $this->callbackPosition) $token[1] .= self::$lead;
	}

	function onReposition(&$token)
	{
		     if ($this->bracketPosition === $this->callbackPosition    ) $token[1] .= self::$lead;
		else if ($this->bracketPosition === $this->callbackPosition + 1) $token[1] = self::$tail . $token[1];
	}

	function onClose(&$token)
	{
		if ($this->bracketPosition === $this->callbackPosition) $token[1] = self::$tail . $token[1];
	}


	protected static $lead, $tail;

	static function __constructStatic()
	{
		$k = '$k' . PATCHWORK_PATH_TOKEN;
		self::$lead = "is_string({$k}=";
		self::$tail = ")&&function_exists('__patchwork_'.{$k})?'__patchwork_'.{$k}:{$k}";
	}
}
