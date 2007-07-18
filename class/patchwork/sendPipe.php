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
		$pipe = array_shift($_GET);
		preg_match_all('/[a-zA-Z_0-9\x80-\xff]+/', $pipe, $pipe);
		patchwork::$agentClass = 'agent__pipe/' . implode('_', $pipe[0]);

		foreach ($pipe[0] as &$pipe)
		{
#>			if (DEBUG) call_user_func(array('pipe_' . $pipe, 'js'));
#>			else
#>			{
				$cpipe = patchwork::getContextualCachePath('pipe/' . $pipe, 'js');
				$readHandle = true;
				if ($h = patchwork::fopenX($cpipe, $readHandle))
				{
					ob_start();
					call_user_func(array('pipe_' . $pipe, 'js'));
					$pipe = ob_get_clean();

					$parser = new jsqueez;
					echo $pipe = $parser->squeeze($pipe);

					fwrite($h, $pipe);
					fclose($h);
					patchwork::writeWatchTable(array('pipe'), $cpipe);
				}
				else
				{
					fpassthru($readHandle);
					fclose($readHandle);
				}
#>			}
			
			echo "\n";
		}

		echo 'w()';

		patchwork::setMaxage(-1);
	}
}
