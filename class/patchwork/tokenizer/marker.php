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


class patchwork_tokenizer_marker extends patchwork_tokenizer_normalizer
{
	protected

	$tag,
	$callbacks = array('tagOpenTag' => T_OPEN_TAG);


	function __construct(parent $parent = null, $tag)
	{
		$this->initialize($parent);
		$this->tag = $tag;
	}

	protected function tagOpenTag(&$token)
	{
		$this->unregister(array(__FUNCTION__ => T_OPEN_TAG));

		$T = $this->tag;

		$token[1] .= "if(!isset(\$a{$T})){global \$a{$T},\$b{$T},\$c{$T};}isset(\$e{$T})||\$e{$T}=false;";
	}
}
