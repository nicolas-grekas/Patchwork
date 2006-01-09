function QSelectSuggest($data, $separator, $synonyms)
{
	if ($separator)
	{
		$synonyms = $synonyms || RegExp.quote($separator);
		$synonyms = new RegExp('[' + $synonyms + ']', 'g');
	}
	else
	{
		$separator = ',';
		$synonyms = /[,;]/g;
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

			$q = $query.substr(0, $selectionStart).split($synonyms);
			$q = $q.pop().replace(new RegExp('^[^0-9a-z' + ACCENT.join('') + ']+', 'i'), '');

			if ($q)
			{
				$q = RegExp.quote($q, 1);
				$q = new RegExp('(^|[^0-9a-z' + ACCENT.join('') + '])' + $q, 'i');

				for (; $i < $data.length; ++$i) if ($data[$i].search($q)>=0) $result[$result.length] = $data[$i];
			}

			$pushBack($result, $query, $selectionStart, 0);
		}

		$driver.onchange = function() {$input.focus();}

		$driver.setValue = function()
		{
			var $idx = $select.selectedIndex,
				$caretPos = getCaret($input),
				$vBegin, $vEnd = $input.value;

			$idx = $idx>0 ? $idx : 0;

			$vBegin = $vEnd.substr(0, $caretPos).split($synonyms);
			$vEnd = $vEnd.substr($caretPos).split($synonyms);

			$vBegin[$vBegin.length - 1] = $options[$idx].text;
			$vEnd[0] = ' ';

			$vBegin = $vBegin.join($separator);
			$vEnd = $vBegin + $separator + $vEnd.join($separator);
			$vBegin = $vBegin.length + $separator.length + 1;
		
			$this.sync($vEnd);

			setSel($input, $vBegin, $vBegin);
			$input.focus();

			return 1;
		}

		return $driver;
	}
}
