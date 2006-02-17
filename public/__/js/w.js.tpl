/*
* eUC
* dUC
* str
* num
* esc
* parseurl
* loadPng
* addOnload
* setcookie
* setboard
* root
* _GET
* _BOARD
* _COOKIE
*/

function t($v)
{
	return typeof $v != 'undefined';
}

function str($var, $default)
{
	return t($var) ? ''+$var : (t($default) ? ''+$default : '');
}

function num($str, $weak)
{
	return $weak ? (typeof $str=='string' && ''+$str/1==$str ? $str/1 : $str) : (parseFloat($str) || 0);
}

function esc($str)
{
	if (typeof $str == 'string')
	{
		$str = $str.replace(
			/&/g, '&amp;').replace(
			/</g, '&lt;').replace(
			/>/g, '&gt;').replace(
			/"/g, '&quot;');
	}

	return $str;
}

function parseurl($param, $delim, $rx, $array)
{
	var $i, $j;
	$array = $array || {};

	$param = $param.split($delim);
	for ($i in $param)
	{
		$param[$i] = $param[$i].replace($rx, '');
		$delim = $param[$i].indexOf('=');
		if ( $delim>0 ) $array[ dUC( $param[$i].substring(0, $delim) ) ] = num(esc(dUC( $param[$i].substring($delim+1) )), 1);
	}

	return $array;
}

function loadPng()
{
	var $this = this, $src = $this.src, $width = $this.width, $height = $this.height;
	if ($src.search(/\.png$/i)>=0)
	{
		$this.src = _GET.__ROOT__+'img/blank.gif';
		$this.style.filter = 'progid:DXImageTransform.Microsoft.AlphaImageLoader(src="'+$src+'",sizingMethod="scale")';
		$this.style.width = $this.width + 'px';
		$this.style.height = $this.height + 'px';
	}
}

function addOnload($function)
{
	var $p = addOnload.p;
	$p[$p.length] = $function;
}


/*
* Set a cookie, similar to PHP's setcookie
*/
function setcookie($name, $value, $expires, $path, $domain, $secure)
{
	document.cookie = eUC($name) + '=' + eUC($value || '') +
		($expires ? '; expires=' + $expires.toGMTString() : '') +
		($path ? '; path=' + eUC($path) : '') +
		($domain ? '; domain=' + eUC($domain) : '') +
		($secure ? '; secure' : '');
}

/*
* Set a board variable
*/
function setboard($name, $value, $window)
{
	if (typeof $name == 'object') for ($value in $name) setboard($value, $name[$value]);
	else
	{
		$window = $window || root;

		function $escape($str)
		{
			return eUC(
				(''+$str).replace(
					/&/g, '%26').replace(
					/=/g, '%3D'
				)
			).replace(
				/_/g, '_5F').replace(
				/!/g, '_21').replace(
				/'/g, '_27').replace(
				/\(/g, '_28').replace(
				/\)/g, '_29').replace(
				/\*/g, '_30').replace(
				/-/g, '_2D').replace(
				/\./g, '_2E').replace(
				/~/g, '_7E').replace(
				/%/g, '_'
			);
		}

		$name = '_26' + $escape($name) + '_3D';

		var $varIdx = $window.name.indexOf($name),
			$varEndIdx;

		if ($varIdx>=0)
		{
			$varEndIdx = $window.name.indexOf('_26', $varIdx + $name.length);
			$window.name = $window.name.substring(0, $varIdx) + ( $varEndIdx>=0 ? $window.name.substring($varEndIdx) : '' );
		}

		$window.name += $name + $escape($value);
	}
}


addOnload.p = [];
if ((root = window).Error)
	// This eval avoids a parse error with browsers not supporting exceptions.
	eval('try{while(((w=root.parent)!=root)&&t(w.name))root=w}catch(w){}');


w = function($rootAgent, $keys, $CIApID)
{
	var $document = document,

		$buffer = '',
		$closeDoc = 0,

		$WexecStack = [],
		$WexecLast = 0,

		$WobStack = [],
		$WobLast = 0,

		$i, $j, $loopIterator = [],
		
		a, v, g,

		$lastInclude = '',
		$includeCache = {};

/* 
*		a : arguments
*		v : local
*		v.$ : parent
*		g : get

*		w.x() : loop construtor for data arrays
*		y() : loop construtor for numbers
*		z() : counter initialization and incrementation
*/

	w = function($context, $code)
	{
		if (!t($context)) return;

		$includeCache[$lastInclude] = $includeCache[$lastInclude] || [$context, $code];

		var $pointer = 0, $arguments = a;

		if ($context)
		{
			for ($i in $context) $context[$i] = esc($context[$i]);
			$context.$ = v = $context;
		}
		else $context = v;

		function $execute()
		{
			$pointer || ($WexecStack[++$WexecLast] = $execute);
			a = $arguments;
			v = $context;

			while ($code[$pointer]>='') switch ($code[$pointer++])
			{
				case 0: // pipe
					$i = $code[$pointer++].split('.');
					$j = $i.length;
					while ($j--) $i[$j] = t(window['P$'+$i[$j]]) ? '' : ('.'+$i[$j]);

					$i = $i.join('');

					if ($i) return $include(g.__ROOT__ + '_?p=' + $i.substr(1), 0, 0);
					break;

				case 1: // agent
					var $agent = $evalNext(),
						$args = $evalNext(),
						$isAgent = $code[$pointer++],
						$keys = $code[$pointer++],
						$data;

					if (!$agent)
					{
						window.E && E('AGENT is undefined: ' + $code[$pointer-4]);
						break;
					}
					
					if ($agent.a && $agent/1)
					{
						$agent = $agent.a();
						while ($j = $agent()) $data = $j;

						$agent = $data['*a'];
						eval('$keys='+$data['*k']);
						delete $data['*a'];
						delete $data['*k'];

						for ($i in $data) if ($i!='$') $args[$i] = $data[$i];
					}

					return $include(
						$isAgent
							? g.__ROOT__ + '_?$=' + eUC($agent.replace(/\\/g, '/'))
							: $agent,
						$args,
						$keys
					);

				case 2: // echo
					$echo( $code[$pointer++] );
					break;

				case 3: // eval echo
					$echo( $evalNext() );
					break;

				case 4: // set
					$WobStack[++$WobLast] = '';
					break;

				case 5: // endset
					$i = $code[$pointer++];
					if (!$i) $i = a;
					else if ($i==1) $i = g;
					else
					{
						$j = $i - 1;
						$i = v;
						while (--$j) $i = $i.$;
					}

					$i[$code[$pointer++]] = num($WobStack[$WobLast--], 1);
					break;

				case 6: // jump
					$pointer += $code[$pointer];
					break;

				case 7: // if
					($evalNext() && ++$pointer) || ($pointer += $code[$pointer]);
					break;

				case 8: // loop
					$i = $evalNext();
					($i && ($i.a || ($i = y($i-0))) && $i.a()() && ++$pointer) || ($pointer += $code[$pointer]);
					$context = v;
					break;

				case 9: // next
					($loopIterator() && ($pointer -= $code[$pointer])) || ++$pointer;
					$context = v;
					break;
			}

			if (--$WexecLast) $WexecStack[$WexecLast]();
			else $closeDoc = 1, w.f();
		}

		function $evalNext()
		{
			return eval('$i=' + $code[$pointer++]);
		}

		function $echo($a)
		{
			if (t($a))
			{
				if ($WobLast) $WobStack[$WobLast] += $a;
				else $buffer += $a;
			}
		}

		function $include($inc, $args, $keys)
		{
			if (t($inc))
			{
				if ($args)
				{
					if ($inc.indexOf('?')==-1) $inc += '?';

					for ($i in $args) $args[$i] = num($args[$i], 1);

					if ($keys)
					{
						for ($i=0; $i<$keys.length; ++$i)
							if (($j = $keys[$i]) && t($args[$j]))
								$inc += '&' + eUC($j) + '=' + eUC($args[$j]);
					}
					else
						for ($i in $args)
							$inc += '&' + eUC($i) + '=' + eUC($args[$i]);

					a = $args;
					$include($inc);
				}
				else
				{
					$lastInclude = $inc;

					if (t($includeCache[$inc])) w($includeCache[$inc][0], $includeCache[$inc][1]);
					else
						$buffer += '<script type="text/javascript" src="' + esc($inc + '&$' + 'v=' + $CIApID) + '"></script >',
						w.f();
				}
			}
		}

		$execute();
	}

	w.f = function()
	{
		var $content;

		$i = $buffer.search(/<\/script>/i);
		if ($i<0)
			$content = $buffer,
			$buffer = '';
		else
			$i += 9,
			$content = $buffer.substring(0, $i) + '<script type="text/javascript" src="js/x"></script>', // Any optimization to save some request here will break IE ...
			$buffer = $buffer.substr($i);

		$document.write($content);

		if ($i<0 && $closeDoc)

			w = addOnload,
			w.$onload = window.onload,

			onload = function()
			{
				if (w.$onload) $i = w.$onload, w.$onload = null, $i();
				for ($i = 0; $i < w.p.length; ++$i) w.p[$i]();
				w.p.length = 0;
				onload = null;
			},

			$document.close();
	}

	w.x = function($data)
	{
		var $block, $offset, $parent, $blockData, $parentLoop, $counter;
		
		function $next()
		{
			$blockData = $data[$block];
			$offset += $j = $blockData[0];

			if ($offset + $j >= $blockData.length) return t($data[++$block])
					? ($offset = 0, $next())
					: (v = v.$, $loopIterator = $parentLoop, 0);

			v = {};
			for ($i = 1; $i <= $j; ++$i) v[ $blockData[$i] ] = esc($blockData[$i + $offset]);
			v.$ = $parent;
			v.iteratorPosition = $counter++;

			return v;
		}

		return $data[0] ? {
			a:function()
			{
				return $parent = v,
					$parentLoop = $loopIterator,
					$counter = $offset = 0, $block = 1,
					$loopIterator = $next;
			},
			toString:function() {return ''+$data[0]}
		} : 0;
	}

	function y($length)
	{
		$length = parseInt($length);
		if (!($length > 0)) return 0;

		var $data = new Array($length + 2);

		$data[0] = $data[1] = 1;
		$data = [$length, $data];

		return w.x($data);
	}

	function z($a, $b, $global)
	{
		$j = $global ? g : a;

		if (!t($j[$a])) $j[$a] = 0;
		$i = $j[$a]/1 || 0;
		$j[$a] += $b;

		return $i;
	}

	$j = location;

	g = parseurl($j.search.replace(/\+/g,'%20').substring(1), '&', /^amp;/);
	g.__QUERY__ = esc($j.search) || '?';
	g.__URI__ = esc($j.href);
	g.__ROOT__ = esc({g$__ROOT__|escape:'js'});
	g.__LANG__ = esc({g$__LANG__|escape:'js'});
	g.__AGENT__ = esc($rootAgent) + ($rootAgent.length ? '/' : '');
	g.__HOST__ = esc($j.protocol+'//'+$j.hostname);

	$j = $j.pathname.substr({g$__ROOT__|length}+$rootAgent.length).split('/');
	for ($i=0; $i<$j.length; ++$i) if ($j[$i]) $loopIterator[$loopIterator.length] = g['__'+($loopIterator.length+1)+'__'] = esc($j[$i]);
	g.__0__ = $loopIterator.join('/');

	_GET = g;

	if ($keys) w(0, [1, "'" + $rootAgent.replace(/'/g, "\\'") + "'", 'g', 1, $keys]);
}

if (window.ScriptEngine) addOnload(function()
{
	var $i = 0, $images = document.images, $len = $images.length;
	for (; $i < $len; ++$i)
	{
		$img = $images[$i];
		$img.onload = loadPng;
		$img.onload();
	}
});

function loadW()
{
	if (window.encodeURI) with (window)
	{
		dUC = decodeURIComponent;
		eUC = encodeURIComponent;
		_BOARD = parseurl(dUC(root.name.replace(/_/g, '%')), '&', /^$/);
		_COOKIE = parseurl(document.cookie, '&', /^amp;/);
		window.a && w(a[0], a[1], a[2]);
	}
	else document.write('<script type="text/javascript" src="js/compat"></script>');
}

loadW();
