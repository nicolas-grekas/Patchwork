/*
* Init this JavaScript Remote Scripting object with
* varname = new QJsrs($url, $POST), where $url is a server script aimed at
* generating the result. Set $POST to true if you want a POST request
* to be made to the server.
*
* Then call this server script _asynchronously_
* with varname.pushCall($vararray, $function)
* Multiple .pushCall() calls are executed sequentialy : the last call is executed
* only when the previous one is finished.
*
* $vararray is an associative array, which is going to be passed to the server script.
*
* $function(result) is called when the result is loaded.
*
* Use varname.pushFunc($vararray, $function) to trigger $function($vararray)
* when the previously pushed callbacks are done.
*
* Cancel the callback pool with varname.abort()
*/

QJsrs = window.QJsrs || (function()
{
var $masterPool = [];

function $QJsrsContext($name)
{
	var $this = this,
		$window = window,
		$document = document,
		$body = $document.getElementById ? $document.getElementById('divQJsrs') : $document.all['divQJsrs'],
		$XMLHttp = $window.XMLHttpRequest ? 2 : $window.ActiveXObject ? 1 : 0,
		$container, $html;

	$this.$load = function($url, $callback)
	{
		$this.$busy = 1;
		$this.$callback = $callback;

		if ($this.p)
			$this.p = $url,
			$url = {g$__ROOT__|escape:'js'}+'QJsrs.html';
		else $url = $url[0] + $url[1];

		if ($XMLHttp)
		{
			$container = $XMLHttp - 1 ? new XMLHttpRequest : new ActiveXObject('Microsoft.XMLHTTP');
			$container.onreadystatechange = function()
			{
				if ($container.readyState==4)
					delete $container.onreadystatechange,
					$html = $container.responseText.replace(/\s+$/, '') || '{}',
					eval('$html=' + $html.substring(30, $html.length-10)),
					$container = $this.$busy = 0,
					$this.$callback($html);
			}

			if ($this.p)
				$container.open('POST', $this.p[0], 1),
				$container.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded'),
				$container.send($this.p[1]);
			else
				$container.open('GET', $url, 1),
				$container.send('');
		}
		else if ($html) frames[$name].location.replace($url);
		else
			$body.innerHTML += '<iframe name='+ $name +' src="'+ $url.replace(/"/g, '&quot;') +'" width=0 height=0 frameborder=0></iframe>',
			$html = 1;
	}
	
	$this.$abort = function()
	{
		if ($container)
			delete $container.onreadystatechange,
			$container.abort(),
			$container = $this.$busy = 0;
		$this.$callback = $this.$abort;
	}
}

loadQJsrs = function($context, $result, $callback)
{
	$context = $masterPool[ parseInt($context.name.substr(1)) ];

	goQJsrs = function()
	{
		$callback = $context.$callback;
		$context.$abort();
		$context.$busy = $callback($result); 
	}

	if (t($result)) setTimeout('goQJsrs()', 0); // Workaround for a bug with relative directories

	return $context;
}

return function($URL, $POST)
{
	var $this = this,
		$pool = [],
		$poolLen = 0, 
		$context, $callback, $url, $i = '?';

	if ($URL.indexOf($i)<0) $URL += $i;

	$POST = $POST ? 1 : 0;

	$this.pushCall = function($vararray, $function)
	{
		$function = $function || function(){};

		$url = '';
		for ($i in $vararray) $url += '&' + eUC($i) + '=' + eUC($vararray[$i]); // Be aware that Konqueror's for(..in..) loop does not preserve the order of declaration
		$url = [$URL, $url, $vararray];

		if ($context) $pool[$poolLen++] = [$url, $function];
		else
		{
			$context = $masterPool.length;
			for ($i = 0; $i < $context; ++$i) if ( !$masterPool[$i].$busy && ($masterPool[$i].p&&1)==$POST ) break;
			if ($i == $context) $masterPool[$i] = new $QJsrsContext('_' + $i), // The '_' prefix prevents confusion of frames['0'] and frames[0] for some browsers

			'' + $function; // Dummy line, but if missing, both IE and Mozilla bug !?
			$callback = $function;

			$context = $masterPool[$i];
			$context.p = $POST;
			$context.$load($url, $release);
		}
	}

	$this.pushFunc = function($vararray, $function)
	{
		$context ? $pool[$poolLen++] = [$vararray, $function, 1] : $function($vararray);
	}

	$this.abort = function()
	{
		$pool = [];
		if ($context) $context.$abort();
		$context = $poolLen = 0;
	}

	function $release($a)
	{
		$callback($a);

		if ($poolLen)
			return $a = $pool[0],
			$pool = $pool.slice(1),
			$poolLen--,
			$callback = $a[1],
			$a[2] ? $release($a[0]) : !$context.$load($a[0], $release);

		$context = 0;
	}
}
})();

document.write('<div id="divQJsrs" style="position:absolute;visibility:hidden"></div>');
