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

	$callbacks = array(
		'tagString' => array(T_STRING, T_UNEXPECTED),
		'tagNew'    => T_NEW,
	);


	protected function tagString(&$token)
	{
		switch ($token[1])
		{
		case '\\':            return $this->tokensUnshift(array(T_NS_SEPARATOR, $token[1]));
		case 'goto':          return $this->tokensUnshift(array(T_GOTO,         $token[1]));
		case 'namespace':     return $this->tokensUnshift(array(T_NAMESPACE,    $token[1]));
		case '__DIR__':       return $this->tokensUnshift(array(T_DIR,          $token[1]));
		case '__NAMESPACE__': return $this->tokensUnshift(array(T_NS_C,         $token[1]));
		}
	}

	protected function tagNew(&$token)
	{
		// Fix `new $foo`, when $foo = 'ns\class';
		// TODO: new ${...}, new $foo[...] and new $foo->...

		$t =& $this->getNextToken($n);

		if (T_VARIABLE === $t[0])
		{
			$n = $this->getNextToken($n);

			if ('[' !== $n[0] && T_OBJECT_OPERATOR !== $n[0])
			{
				$t[1] = "\${is_string($\x9D={$t[1]})&&($\x9D=strtr($\x9D,'\\\\','_'))?\"\x9D\":\"\x9D\"}";
			}
		}
	}
}
