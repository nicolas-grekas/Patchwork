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
	static function call($agent)
	{
		header('Content-Type: text/javascript');
		CIA::setMaxage(-1);

		echo 'w.k(',
			CIA::$versionId, ',',
			jsquote( $_SERVER['CIA_BASE'] ), ',',
				jsquote( 'agent_index' == $agent ? '' : str_replace('_', '/', substr($agent, 6)) ), ',',
			jsquote( isset($_GET['__0__']) ? $_GET['__0__'] : '' ), ',',
			'[', implode(',', array_map('jsquote', CIA::agentArgv($agent))), ']',
		')';
	}
}
