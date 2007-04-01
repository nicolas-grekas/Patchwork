{***************************************************************************
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
 ***************************************************************************}

function QSelectSuggest($data, $separator, $separatorRx)
{
	if (!($separator && $separatorRx))
	{
		$separator = ' ';
		$separatorRx = '[\\s,;]+';
	}

	return function($this, $input, $select, $options)
	{
		var $driver = QSelectSearch($data)($this, $input, $select, $options);

		$driver.fixTab = 1;

		$driver.search = function($query, $pushBack, $selectionStart)
		{
			if ('*' == $query) return $pushBack($data.slice(0, 15));

			var $result = [],
				$i = 0,
				$q;

			$selectionStart = $selectionStart || $query.length;

			$q = $query.substr($selectionStart).split(new RegExp($separatorRx + '.*$'));

			if ('' == $q[0])
			{
				$q = $query.substr(0, $selectionStart).replace(new RegExp('^.*' + $separatorRx), '').replace(new RegExp('^[^0-9a-z' + ACCENT.join('') + ']+', 'i'), '');

				if ($q)
				{
					$q = RegExp.quote($q, 1);
					$q = new RegExp('(^|[^0-9a-z' + ACCENT.join('') + '])' + $q, 'i');

					for (; $i < $data.length; ++$i) if ($q.test($data[$i])) $result[$result.length] = $data[$i];
				}
			}

			$pushBack($result, $query, $selectionStart, 0);
		}

		$driver.onchange = function() {$input.focus();}

		$driver.setValue = function()
		{
			var $idx = $select.selectedIndex,
				$vBegin, $vEnd = $input.value,
				$caretPos = getCaret($input);

			$idx = $idx>0 ? $idx : 0;

			$vBegin = $vEnd.substr(0, $caretPos);
			$vEnd = $vEnd.substr($caretPos);

			$vBegin = $vBegin.match(new RegExp('^(.*' + $separatorRx + ')'));
			$vBegin = $vBegin ? $vBegin[1] : '';

			$vBegin += $options[$idx].text + $separator;
			$vEnd = $vBegin + $vEnd;
			$vBegin = $vBegin.length;

			$this.sync($vEnd);

			setSel($input, $vBegin, $vBegin);
			$input.focus();

			return 1;
		}

		return $driver;
	}
}
