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


class extends patchwork
{
	static function call()
	{
		$template = array_shift($_GET);
		$template = str_replace('\\', '/', $template);
		$template = str_replace('../', '/', $template);

		echo 'w(0';

		$ctemplate = patchwork::getContextualCachePath("templates/$template", 'txt');

		PATCHWORK_TURBO || patchwork::syncTemplate($template, $ctemplate);

		$readHandle = true;

		if ($h = patchwork::fopenX($ctemplate, $readHandle))
		{
			patchwork::openMeta('agent__template/' . $template, false);
			$compiler = new ptlCompiler_js(false);
			echo $template = ',[' . $compiler->compile($template . '.ptl') . '])';
			fwrite($h, $template);
			fclose($h);
			list(,,, $watch) = patchwork::closeMeta();
			patchwork::writeWatchTable($watch, $ctemplate);
		}
		else
		{
			fpassthru($readHandle);
			fclose($readHandle);
		}

		patchwork::setMaxage(-1);
	}
}
