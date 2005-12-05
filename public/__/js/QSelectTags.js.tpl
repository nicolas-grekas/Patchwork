function QSelectTags($data)
{
	$data.length--;

	return function($query, $pushBack, $selectionStart)
	{
		if ('*' == $query) return $pushBack($data);
		
		var $result = [],
			$i = 0,
			$qBegin, $qEnd,
			$selectionLength = 0;

		$selectionStart = $selectionStart || $query.length;

		$qBegin = stripAccents($query.substr(0, $selectionStart), -1).replace(/[^a-z0-9]+/g, ' ').replace(/^ /, '');
		$qEnd = stripAccents($query.substr($selectionStart), -1).replace(/[^a-z0-9]+/g, ' ');

		$selectionStart = $qBegin.length;

		$qBegin = $qBegin.split(' ');
		$qEnd = $qEnd.split(' ');

		$query = $qBegin.pop();

		if ($query) for (; $i < $data.length; ++$i) if ($data[$i].indexOf($query)==0) $result[$result.length] = $data[$i];

		if ($result[0])
		{
			$selectionLength = $result[0].length - $query.length;
			$qEnd.shift();
			$query = $result[0] + ' ';

			if (1 == $result.length) $result = [];
		}

		$qBegin.push($query);

		$pushBack($result, $qBegin.join(' ') + $qEnd.join(' '), $selectionStart, $selectionLength);
	}
}
