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


class extends agent
{
	public $get = 'id';

	function control() {}

	function compose($o)
	{
		if ($this->get->id)
		{
			$this->expires = 'onmaxage';
			patchwork::setGroup('private');

			if (function_exists('upload_progress_meter_get_info'))
			{
				$o = (object) @upload_progress_meter_get_info($this->get->id);
			}
			else if (function_exists('uploadprogress_get_info'))
			{
				$o = (object) @uploadprogress_get_info($this->get->id);
			}
		}
		else $this->maxage = -1;

		return $o;
	}
}
