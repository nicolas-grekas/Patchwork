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


// New token to match the end of the source code
define('T_ENDPHP', -2);

class patchwork_tokenizer_normalizer extends patchwork_tokenizer
{
	function __construct(patchwork_tokenizer &$parent = null)
	{
		parent::__construct($parent);

		$this->register($this, array(
			'openEchoTag' => T_OPEN_TAG_WITH_ECHO,
			'openTag'     => T_OPEN_TAG,
			'closeTag'    => T_CLOSE_TAG,
			'fixVar'      => T_VAR,
		));
	}

	function tokenize($code)
	{
		if (false !== strpos($code, "\r"))
		{
			$code = str_replace("\r\n", "\n", $code);
			$code = strtr($code, "\r", "\n");
		}

		if (0 === strncmp($code, "\xEF\xBB\xBF", 3))
		{
			$this->register($this, 'stripBom');
		}

		return parent::tokenize($code);
	}

	function getTokens($code)
	{
		$code = parent::getTokens($code);

		$last = array_pop($code);

		if (T_CLOSE_TAG === $last[0])
		{
			$last[0] = $last[1] = ';';
		}

		$code[] = $last;

		if (T_INLINE_HTML === $last[0])
		{
			$code[] = array(T_OPEN_TAG, '<?php ');
		}

		$code[] = array(T_ENDPHP, '');

		return $code;
	}

	function openEchoTag(&$token, $t)
	{
		$t->code[--$t->position] = array(T_ECHO, 'echo');
		$t->code[--$t->position] = array(T_OPEN_TAG, $token[1]);

		return false;
	}

	function openTag(&$token)
	{
		$token[1] = substr_count($token[1], "\n");
		$token[1] = '<?php' . ($token[1] ? str_repeat("\n", $token[1]) : ' ');
	}

	function closeTag(&$token)
	{
		$token[1] = substr_count($token[1], "\n");
		$token[1] = str_repeat("\n", $token[1]) . '?'.'>';
	}

	function stripBom(&$token, $t)
	{
		$t->unregister($this, __FUNCTION__);
		$token[1] = substr($token[1], 3);
		if ('' === $token[1]) return false;
	}

	function fixVar($token, $t)
	{
		$t->code[--$t->position] = array(T_PUBLIC, 'public');

		return false;
	}
}
