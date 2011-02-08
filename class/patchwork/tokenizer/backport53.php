<?php /*********************************************************************
 *
 *   Copyright : (C) 2011 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/


// New tokens since PHP 5.3
defined('T_GOTO')         || patchwork_tokenizer::createToken('T_GOTO');
defined('T_DIR' )         || patchwork_tokenizer::createToken('T_DIR');
defined('T_NS_C')         || patchwork_tokenizer::createToken('T_NS_C');
defined('T_NAMESPACE')    || patchwork_tokenizer::createToken('T_NAMESPACE');
defined('T_NS_SEPARATOR') || patchwork_tokenizer::createToken('T_NS_SEPARATOR');


class patchwork_tokenizer_backport53 extends patchwork_tokenizer
{
	protected

	$tag = "\x90",
	$callbacks = array(
		'tagString' => T_STRING,
		'tagNew'    => T_NEW,
	);


	protected function getTokens($code)
	{
		if (false !== strpos($code, '\\') && 0 > version_compare(PHP_VERSION, '5.3.0'))
		{
			while (false !== strpos($code, '~' . $this->tag)) $this->tag = $this->tag[0] . mt_rand();

			$code = preg_replace("/([^\\\\])(\\\\[^\\\\$'\"])/", "$1~{$this->tag}$2", $code);

			$this->register(array(
				'tagNsSep' => '~',
				'fixNsSep' => array(T_COMMENT, T_DOC_COMMENT, T_CONSTANT_ENCAPSED_STRING, T_INLINE_HTML, T_COMPILER_HALTED),
			));

			$this->register(array(0 > version_compare(PHP_VERSION, '5.2.3') ? 'fixNsSep522' : 'fixNsSep' => T_ENCAPSED_AND_WHITESPACE));
		}

		return parent::getTokens($code);
	}

	protected function tagString(&$token)
	{
		switch ($token[1])
		{
		case 'goto':          return $this->tokensUnshift(array(T_GOTO,      $token[1]));
		case 'namespace':     return $this->tokensUnshift(array(T_NAMESPACE, $token[1]));
		case '__DIR__':       return $this->tokensUnshift(array(T_DIR,       $token[1]));
		case '__NAMESPACE__': return $this->tokensUnshift(array(T_NS_C,      $token[1]));
		}
	}

	protected function tagNew(&$token)
	{
		// TODO: new ${...}, new $foo[...] and new $foo->...

		$t =& $this->getNextToken();

		if (T_VARIABLE === $t[0])
		{
			$n = $this->getNextToken(1);

			if ('[' !== $n[0] && T_OBJECT_OPERATOR !== $n[0])
			{
				$t[1] = "\${is_string($\x9D={$t[1]})&&($\x9D=strtr($\x9D,'\\\\','_'))?\"\x9D\":\"\x9D\"}";
			}
		}
	}

	protected function tagNsSep(&$token)
	{
		$t =& $this->tokens;
		$i =& $this->index;

		if (!isset($t[$i][1]) || $this->tag !== $t[$i][1]) return;

		$t[$i] = array(T_NS_SEPARATOR, '\\');

		return false;
	}

	protected function fixNsSep(&$token)
	{
		$token[1] = str_replace("~{$this->tag}\\", '\\', $token[1]);
	}

	protected function fixNsSep522(&$token)
	{
		if ('~' !== substr($token[1], -1)) return;

		$t =& $this->tokens;
		$i =& $this->index;

		if (!isset($t[$i][1], $t[$i+1][1][0]) || $this->tag !== $t[$i][1] || '\\' !== $t[$i+1][1][0]) return;

		if ('~' === $token[1]) unset($t[$i++]);
		else $t[$i] = array(T_ENCAPSED_AND_WHITESPACE, substr($token[1], 0, -1));

		return false;
	}
}
