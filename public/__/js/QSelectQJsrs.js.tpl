<!-- AGENT 'js' __0__='QJsrs' -->

function QSelectQJsrs($QJsrs)
{
	$QJsrs = new QJsrs($QJsrs, 1);

	return function($query, $pushBack)
	{
		$QJsrs.abort();
		$QJsrs.pushCall({q: $query}, $pushBack);
	}
}
