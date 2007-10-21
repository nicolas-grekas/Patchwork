<?php /*********************************************************************
 *
 *   Copyright : (C) 2007 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/gpl.txt GNU/GPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 3 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/


class
{
	static function php($g, $lang)
	{
		if (isset($CONFIG['i18n.lang_list'][$lang]))
		{
			$url = $g->__LANG__ ? $CONFIG['i18n.lang_list'][$g->__LANG__] : '__';
			$url = preg_replace("'\\b{$url}\\b'", $CONFIG['i18n.lang_list'][$lang], $g->__URI__, 1);
		}
		else $url = $g->__URI__;

		return $url;
	}

	static function js()
	{
		?>/*<script>*/

P$switchLang = function($g, $lang)
{
	var $lang_list = {'':''<?php foreach ($CONFIG['i18n.lang_list'] as $k => $v) echo ',',jsquote($k),':',jsquote($v);?>};

	return t($lang_list[$lang])
		? $g.__URI__.replace(new RegExp('\\b' + ($g.__LANG__ ? $lang_list[$g.__LANG__] : '__') + '\\b'), $lang_list[$lang])
		: $g.__URI__;
}

<?php	}
}
