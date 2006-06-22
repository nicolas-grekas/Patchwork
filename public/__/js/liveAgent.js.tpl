if (!window.liveAgent)
{

<!-- AGENT 'js/QJsrs' -->

function liveAgent($AGENT, $POST, $antiXSJ, $HOME)
{
	$HOME = $HOME || home('');
	$AGENT = $HOME + '_?x$=' + eUC($AGENT);

	var $QJsrs = new QJsrs($AGENT, $POST, $antiXSJ),
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

	$QJsrs.driver = function($callback, $text)
	{
		var $originalW = window.w;
		window.w = w;

		$originalDriver($callback, $text);

		window.w = $originalW;
	}

	return $QJsrs;
}

}
