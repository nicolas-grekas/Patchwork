<?php /*********************************************************************
 *
 *   Copyright : (C) 2007 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 ***************************************************************************/

class extends patchwork
{
	static function call($agent)
	{
		header('Content-Type: text/javascript');
		p::setMaxage(-1);
		p::setLang($_GET['k$']);

		echo 'w.k(',
			p::$appId, ',',
			jsquote( p::$base ), ',',
			jsquote( 'agent_index' === $agent ? '' : str_replace('_', '/', substr($agent, 6)) ), ',',
			jsquote( isset($_GET['__0__']) ? $_GET['__0__'] : '' ), ',',
			'[', implode(',', array_map('jsquote', p::agentArgs($agent))), ']',
		')';
	}
}
