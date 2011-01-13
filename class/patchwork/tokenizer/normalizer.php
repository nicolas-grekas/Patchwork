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


// Match the end of the source code
patchwork_tokenizer::defineNewToken('T_ENDPHP');


class patchwork_tokenizer_normalizer extends patchwork_tokenizer
{
	protected

	$callbacks = array(
		'tagOpenEchoTag'  => T_OPEN_TAG_WITH_ECHO,
		'tagOpenTag'      => T_OPEN_TAG,
		'tagCloseTag'     => T_CLOSE_TAG,
		'fixVar'          => T_VAR,
		'tagHaltCompiler' => T_HALT_COMPILER,
	);


	function parse($code, $verify_utf8 = true)
	{
		if (false !== strpos($code, "\r"))
		{
			$code = str_replace("\r\n", "\n", $code);
			$code = strtr($code, "\r", "\n");
		}

		if ($verify_utf8)
		{
			if (!preg_match('//u', $code))
			{
				$this->setError("File encoding is not valid UTF-8", 0);
			}

			if (0 === strncmp($code, "\xEF\xBB\xBF", 3))
			{
				// substr_replace() is for mbstring overloading resistance
				$code = substr_replace($code, '', 0, 3);
			}
		}


		return parent::parse($code);
	}

	protected function getTokens($code)
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

	protected function tagOpenEchoTag(&$token)
	{
		$this->tagOpenTag($token);

		return $this->tokensUnshift(
			array(T_ECHO, 'echo'),
			array(T_OPEN_TAG, $token[1])
		);
	}

	protected function tagOpenTag(&$token)
	{
		$token[1] = substr_count($token[1], "\n");
		$token[1] = '<?php' . ($token[1] ? str_repeat("\n", $token[1]) : ' ');
	}

	protected function tagCloseTag(&$token)
	{
		$token[1] = substr_count($token[1], "\n");
		$token[1] = str_repeat("\n", $token[1]) . '?'.'>';
	}

	protected function fixVar(&$token)
	{
		return $this->tokensUnshift(array(T_PUBLIC, 'public'));
	}

	protected function tagHaltCompiler(&$token)
	{
		$this->unregister(array(__FUNCTION__ => T_HALT_COMPILER));
		return $this->tokensUnshift(';', array(T_ENDPHP, ''));
	}
}
