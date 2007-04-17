/***************************************************************************
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

<!-- AGENT 'js/liveAgent' -->

function QSelectLiveAgent($liveAgent, $loop, $key)
{
	$liveAgent = new liveAgent($liveAgent);

	return function($this, $input, $select, $options)
	{
		var $driver = QSelectSearch()($this, $input, $select, $options);

		$driver.search = function($query, $pushBack)
		{
			$liveAgent.replace(
				{q: $query},
				function($result)
				{
					if ($result)
					{
						var $l = $result[$loop], $i = 0, $k = $key;

						$result = [];

						for (; $i < $l.length; ++$i) $result.push( $l[$i][$k] );

						$pushBack($result);
					}
				}
			);
		}

		return $driver;
	}
}
