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
	static function call()
	{
		$pipe = array_shift($_GET);
		preg_match_all('/[a-zA-Z_0-9\x80-\xff]+/', $pipe, $pipe);
		p::$agentClass = 'agent__pipe/' . implode('_', $pipe[0]);

		foreach ($pipe[0] as &$pipe)
		{
#>			if (DEBUG) call_user_func(array('pipe_' . $pipe, 'js'));
#>			else
#>			{
				$cpipe = p::getContextualCachePath('pipe/' . $pipe, 'js');
				$readHandle = true;
				if ($h = p::fopenX($cpipe, $readHandle))
				{
					ob_start();
					call_user_func(array('pipe_' . $pipe, 'js'));
					$pipe = ob_get_clean();

					$parser = new jsqueez;
					echo $pipe = $parser->squeeze($pipe);

					fwrite($h, $pipe);
					fclose($h);
					p::writeWatchTable(array('pipe'), $cpipe);
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

		p::setMaxage(-1);
	}
}
