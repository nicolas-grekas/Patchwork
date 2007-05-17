<?php /*********************************************************************
 *
 *   Copyright : (C) 2006 Nicolas Grekas. All rights reserved.
 *   Email     : nicolas.grekas+patchwork@espci.org
 *   License   : http://www.gnu.org/licenses/gpl.txt GNU/GPL, see COPYING
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/


class extends agent
{
	const contentType = '';

	protected $data = array();

	function compose($o)
	{
		$o->DATA = '/*<script type="text/javascript">/**/q="'
			. str_replace(array('\\', '"'), array('\\\\', '\\"'), $this->getJs($this->data))
			. '"//</script>'
			. '<script type="text/javascript" src="' . patchwork::__BASE__() . 'js/QJsrsHandler"></script>';

		return $o;
	}

	protected function getJs(&$data)
	{
		if (is_object($data) || is_array($data))
		{
			$a = '{';

			foreach ($data as $k => &$v) $a .= "'" . jsquote($k, false) . "':" . $this->getJs($v) . ',';

			$k = strlen($a);
			if ($k > 1) $a{strlen($a)-1} = '}';
			else $a = '{}';
		}
		else $a = jsquote((string) $data);

		return $a;
	}
}
