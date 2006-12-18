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
	public $argv = array('id');

	function control() {}

	function compose($o)
	{
		if ($this->argv->id)
		{
			$this->expires = 'onmaxage';
			CIA::setGroup('private');

			if (function_exists('upload_progress_meter_get_info'))
			{
				$o = (object) @upload_progress_meter_get_info($this->argv->id);
			}
			else if (function_exists('uploadprogress_get_info'))
			{
				$o = (object) @uploadprogress_get_info($this->argv->id);
			}
		}
		else $this->maxage = -1;

		return $o;
	}
}
