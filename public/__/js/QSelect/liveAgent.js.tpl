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
