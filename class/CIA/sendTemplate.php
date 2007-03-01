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


class extends CIA
{
	static function call()
	{
		$template = array_shift($_GET);
		$template = str_replace('\\', '/', $template);
		$template = str_replace('../', '/', $template);

		echo 'w(0';

		$ctemplate = CIA::getContextualCachePath("templates/$template", 'txt');
		$readHandle = true;
		if ($h = CIA::fopenX($ctemplate, $readHandle))
		{
			CIA::openMeta('agent__template/' . $template, false);
			$compiler = new iaCompiler_js(false);
			echo $template = ',[' . $compiler->compile($template . '.tpl') . '])';
			fwrite($h, $template, strlen($template));
			fclose($h);
			list(,,, $watch) = CIA::closeMeta();
			CIA::writeWatchTable($watch, $ctemplate);
		}
		else
		{
			fpassthru($readHandle);
			fclose($readHandle);
		}

		CIA::setMaxage(-1);
	}
}
