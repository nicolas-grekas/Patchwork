/*
* Init this JavaScript Remote Scripting object with
* varname = new QJsrs($url, $POST), where $url is a server script aimed at
* generating the result. Set $POST to true if you want a POST request
* to be made to the server.
*
* Then call this server script _asynchronously_
* with varname.push($vararray, $function)
* Multiple .push() calls are executed sequentialy : the last call is executed
* only when the previous one is finished.
*
* use varname.replace($vararray, $function) to empty the call sequence and then do the request
*
* $vararray is an associative array, which is going to be passed to the server script.
*
* $function(result) is called when the result is loaded.
*
* Cancel the callback pool with varname.abort()
*/

$win = self;

if (!$win.QJsrs)
{

function $emptyFunction() {};
document.write('<div id="divQJsrs" style="position:absolute;visibility:hidden"></div>');

// Preload the XMLHttp object and detects browser capabilities.
QJsrs = $win.ScriptEngineMajorVersion;
if (QJsrs && QJsrs() >= 5) eval('try{QJsrs=new ActiveXObject("Microsoft.XMLHTTP")&&2}catch(QJsrs){QJsrs=1}');
else QJsrs = $win.XMLHttpRequest ? new XMLHttpRequest && 3 : 1;

QJsrs = (function()
{

var $contextPool = [],
	$loadCounter = 0,
	$masterTimer = 0,
	$document = 0,
	$emptyFunction = $win.$emptyFunction,
	$XMLHttp = QJsrs - 1;

function $QJsrsContext($name)
{
	var $this = this,
		$container,
		$html;

	$this.$load = function($url, $callback, $post, $local)
	{
		$this.$busy = 1;
		$this.$callback = $callback;

		if ($post || !$local)
			$url[3] = $post,
			$url[4] = $local,
			$this.q = $url,
			$url = _GET.__ROOT__ + 'QJsrs.html';
		else $url = $url[0] + $url[1];

		if ($local && $XMLHttp)
		{
			$container = $XMLHttp - 1 ? new XMLHttpRequest : new ActiveXObject('Microsoft.XMLHTTP');
			$container.onreadystatechange = function()
			{
				if ($container.readyState==4)
					$container.onreadystatechange = $emptyFunction,
					$html = $container.responseText.replace(/<\/.*/, '').substr(33),
					eval('$html=' + $html),
					eval('$html=' + $html),
					$container = $this.$busy = 0,
					$this.$callback($html);
			}

			if ($post)
				$container.open('POST', $this.q[0], 1),
				$container.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded'),
				$container.send($this.q[1]);
			else
				$container.open('GET', $url, 1),
				$container.send('');
		}
		else if ($html) frames[$name].location.replace($url);
		else
		{
			if (!$document) $document = document, $document = $document.getElementById ? $document.getElementById('divQJsrs') : $document.all['divQJsrs'];

			$document.innerHTML += '<iframe name='+ $name +' src="'+ $url.replace(/"/g, '&quot;') +'" width=0 height=0 frameborder=0></iframe>',
			$html = 1;
		}
	}

	$this.$abort = function()
	{
		if ($container)
			$container.onreadystatechange = $emptyFunction,
			$container.abort(),
			$container = $this.$busy = 0;
		$this.$callback = $this.$abort;
	}
}

$win.loadQJsrs = function($context, $result, $callback)
{
	$context = $contextPool[ parseInt($context.name.substr(1)) ];

	if ($result>='' || $result < 0) $QJsrs.$setTimeout(function()
	{
		$callback = $context.$callback;
		$context.$abort();
		$context.$busy = $callback($result);
	}, 0); // Workaround for a bug with relative directories

	return $context;
}

function $QJsrs($URL, $POST)
{
	var $this = this,
		$pool = [],
		$poolLen = 0,
		$localTimer = 0,
		$context, $callback, $url, $i = '?';

	if ($URL.indexOf($i)<0) $URL += $i;

	$URL = root($URL);
	$LOCAL = 0 == $URL.indexOf(_GET.__HOST__);
	$POST = $POST ? 1 : 0;

	$this.replace = function($vararray, $function)
	{
		$this.abort();
		$this.push($vararray, $function);
	}

	$this.push = function($vararray, $function)
	{
		if (!$loadCounter)
		{
			if ($masterTimer) $masterTimer = clearTimeout($masterTimer);
			else $QJsrs.onloading();
		}

		++$loadCounter;

		$function = $function || $emptyFunction;

		$url = '';
		for ($i in $vararray) $url += '&' + eUC($i) + '=' + eUC($vararray[$i]); // Be aware that Konquerors for(..in..) loop does not preserve the order of declaration
		$url = [$URL, $url, $vararray];

		if ($context) $pool[$poolLen++] = [$url, $function];
		else
		{
			if ($localTimer) $localTimer = clearTimeout($localTimer);
			else $this.onloading();

			$context = $contextPool.length;
			for ($i = 0; $i < $context; ++$i) if (!$contextPool[$i].$busy) break;
			if ($i == $context) $contextPool[$i] = new $QJsrsContext('_' + $i), // The '_' prefix prevents confusion of frames['0'] and frames[0] for some browsers

			'' + $function; // Dummy line, but if missing, both IE and Mozilla bug !?
			$callback = $function;

			$context = $contextPool[$i];
			$context.$load($url, $release, $POST, $LOCAL);
		}
	}

	$this.abort = function()
	{
		if ($context) $context.$abort();
		$callback && $release(false, 1);
	}

	function $release($a, $abort)
	{
		$callback($a);

		--$loadCounter;

		if ($poolLen)
		{
			$a = $pool[0];
			$pool = $pool.slice(1);
			$poolLen--;
			$callback = $a[1];

			return $abort ? $release(false, 1) : !$context.$load($a[0], $release, $POST, $LOCAL);
		}

		$callback = $context = 0;

		$localTimer = $QJsrs.$setTimeout($QJsrs.onloaded, 10);
		if (!$loadCounter) $masterTimer = $QJsrs.$setTimeout($this.onloaded, 10);
	}

	$this.onloading = $this.onloaded = $emptyFunction;
}

$QJsrs.$setTimeoutId = 0;
$QJsrs.$setTimeoutPool = [];
$QJsrs.onloading = $QJsrs.onloaded = $emptyFunction;

return $QJsrs;

})();

QJsrs.$setTimeout = function($function, $timeout, $i)
{
	$i = ++QJsrs.$setTimeoutId;
	QJsrs.$setTimeoutPool[$i] = $function;
	return setTimeout('QJsrs.$setTimeoutPool['+$i+']();QJsrs.$setTimeoutPool['+$i+']=null', $timeout);
}

}
