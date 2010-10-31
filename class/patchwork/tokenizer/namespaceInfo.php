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


class patchwork_tokenizer_namespaceInfo extends patchwork_tokenizer
{
	protected

	$namespace = '',
	$callbacks = array('tagNs' => T_NAMESPACE),
	$shared    = 'namespace';


	function tagNs(&$token)
	{
		$this->register('tagNsStart');
	}

	function tagNsStart(&$token)
	{
		$this->unregister(__FUNCTION__);

		if (T_STRING === $token[0])
		{
			$this->namespace = $token[1] . '\\';

			$this->register($this->callbacks = array(
				'tagNsName' => T_STRING,
				'tagNsEnd'  => array('{', ';'),
			));
		}
		else if ('{' === $token[0])
		{
			$this->namespace = '';
		}
	}

	function tagNsName(&$token)
	{
		$this->namespace .= $token[1] . '\\';
	}

	function tagNsEnd(&$token)
	{
		$this->unregister();
	}
}
