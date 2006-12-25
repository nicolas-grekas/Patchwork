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


class extends agent_fckeditor_browser
{
	public $argv = array('Type');

	function compose($o)
	{
		$this->argv->Command = 'FileUpload';
		$this->argv->CurrentFolder = '/';

		$o = parent::compose($o);

		if (isset($o->currentUrl)) $o->url = $o->currentUrl . $o->filename;

		return $o;
	}
}
