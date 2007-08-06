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


/**
 * Hook executed only once, when application is initialized
 */

class extends self
{
	static function call()
	{
		parent::call();


		// Register the crontab

		$file = patchwork::$cachePath . 'crontabId';

		$id = file_exists($file) ? file_get_contents($file) : 0;
		$id && pTask::cancel($id);
		$id = pTask::schedule(new pTask_crontab);

		file_put_contents($file, $id);
	}
}
