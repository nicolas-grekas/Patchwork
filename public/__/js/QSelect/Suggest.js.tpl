function QSelectSuggest($data, $separator, $separatorRx)
{
	if (!($separator && $separatorRx))
	{
		$separator = ' ';
		$separatorRx = '[\s,;]+';
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

					for (; $i < $data.length; ++$i) if ($data[$i].search($q)>=0) $result[$result.length] = $data[$i];
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
