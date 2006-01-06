<!-- AGENT 'js' __0__='QJsrs' -->

function QSelectQJsrs($QJsrs)
{
	$QJsrs = new QJsrs($QJsrs, 1);

	return function($this, $input, $select, $options)
	{
		var $driver = QSelectSearch()($this, $input, $select, $options);

		$driver.search = function($query, $pushBack)
		{
			$QJsrs.abort();
			$QJsrs.pushCall({q: $query}, $pushBack);
		}

		return $driver;
	}
}
