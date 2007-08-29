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


class extends patchwork
{
	static function call()
	{
		$template = array_shift($_GET);
		$template = str_replace('\\', '/', $template);
		$template = str_replace('../', '/', $template);

		echo 'w(0';

		$ctemplate = p::getContextualCachePath("templates/$template", 'txt');

		TURBO || p::syncTemplate($template, $ctemplate);

		$readHandle = true;

		if ($h = p::fopenX($ctemplate, $readHandle))
		{
			p::openMeta('agent__template/' . $template, false);
			$compiler = new ptlCompiler_js(false);
			echo $template = ',[' . $compiler->compile($template . '.ptl') . '])';
			fwrite($h, $template);
			fclose($h);
			list(,,, $watch) = p::closeMeta();
			p::writeWatchTable($watch, $ctemplate);
		}
		else
		{
			fpassthru($readHandle);
			fclose($readHandle);
		}

		p::setMaxage(-1);
	}
}
