{*/**************************************************************************
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
 **************************************************************************/*}

<!-- AGENT 'js/QJsrs' -->

function liveAgent($AGENT, $POST, $antiXSJ, $HOME, $XMLHttpPreferred)
{
	$HOME = $HOME || home('', 0, 1);
	$AGENT = $HOME + '_?x$=' + eUC($AGENT);

	var $QJsrs = new QJsrs($AGENT, $POST, $antiXSJ, $XMLHttpPreferred),
		$originalDriver = $QJsrs.driver,
		w = {
			x:function($data)
			{
				var $a = [], $dataLen = $data.length, $counter = -1, $i = 1, $block, $blockLen, $modulo, $keys, $j, $b, $k;

				for (; $i < $data.length; ++$i)
				{
					$block = $data[$i];
					$blockLen = $block.length;
					$modulo = $block[0];
					$j = $modulo + 1;

					$keys = $block.slice(1, $j);

					for (; $j < $blockLen; $j+=$modulo)
					{
						$b = $a[++$counter] = {};

						for ($k = 0; $k < $modulo; ++$k) $b[ $keys[$k] ] = $block[$j + $k];
					}
				}

				return $a;
			}
		};

	$QJsrs.driver = function($callback, $text, $raw)
	{
		var $originalW = window.w;
		window.w = w;

		$originalDriver($callback, $text, $raw);

		window.w = $originalW;
	}

	return $QJsrs;
}
