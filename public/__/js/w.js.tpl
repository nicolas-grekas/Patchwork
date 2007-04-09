/***************************************************************************
 *
 *   Copyright : (C) 2006 Nicolas Grekas. All rights reserved.
 *   Email     : nicolas.grekas+patchwork@espci.org
 *   License   : http://www.gnu.org/licenses/gpl.txt GNU/GPL, see COPYING
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/


/*
* eUC
* dUC
* str
* num
* esc
* unesc
* parseurl
* onDOMLoaded
* BOARD
*/

footerHtml = [];
antiCSRF = '';

function t($v, $type)
{
	return $type ? (typeof $v == $type) : (typeof $v != 'undefined');
}

function str($var, $default)
{
	return t($var) ? ''+$var : (t($default) ? ''+$default : '');
}

function num($str, $weak)
{
	return $weak
		? (
			  t($str)
			? (
				  t($str, 'string') && ''+$str/1==$str
				? $str/1
				: $str
			) : ''
		) : (parseFloat($str) || 0);
}

function esc($str)
{
	return t($str, 'string')
		? $str.replace(
			/&/g, '&amp;').replace(
			/</g, '&lt;').replace(
			/>/g, '&gt;').replace(
			/"/g, '&quot;'
		) : $str;
}

function unesc($str)
{
	return t($str, 'string')
		? $str.replace(
			/&#039;/g, "'").replace(
			/&quot;/g, '"').replace(
			/&gt;/g  , '>').replace(
			/&lt;/g  , '<').replace(
			/&amp;/g , '&'
		) : $str;
}

function parseurl($param, $delim, $rx, $array)
{
	var $i;
	$array = $array || {};

	$param = $param.split($delim);
	while ($param.length)
	{
		$i = $param.shift();
		if ($rx) $i = $i.replace($rx, '');
		$delim = $i.indexOf('=');
		if ( $delim>0 ) $array[ dUC( $i.substring(0, $delim) ) ] = num(esc(dUC( $i.substring($delim+1) )), 1);
	}

	return $array;
}

function syncCSRF($form)
{
	var $a, $antiCSRF = antiCSRF;

	if (!$form)
	{
		$form = document.forms;
		$form = $form[$form.length - 1];
	}

	if ($antiCSRF && 'post' == $form.method.toLowerCase())
	{
		if (($form.action + '/').indexOf({g$__BASE__|js})) return;

		if ($form.T$) $form.T$.value = $antiCSRF;
		else if ($form.firstChild)
		{
			$a = document.createElement('input');

			$a.type = 'hidden';
			$a.name = 'T$';
			$a.value = $antiCSRF;

			$form.insertBefore($a, $form.firstChild);
		}
		else $form.innerHTML += '<input type="hidden" name="T$" value="' + $antiCSRF + '" />';

		if (!$form.syncCSRF)
		{
			$a = $form.onsubmit;
			$form.syncCSRF = 1;
			$form.onsubmit = function()
			{
				this.T$.value = antiCSRF;
				return $a && $a();
			}
		}

		$form = 0;
	}
}

function resyncCSRF()
{
	var $resyncCSRF = resyncCSRF,
		$document = document,
		$forms = $document.forms,
		$cookie = $document.cookie.match(/(^|; )T\$=([0-9a-f]+)/);

	$cookie = $cookie && $cookie[2];

	if (antiCSRF = antiCSRF || $cookie)
	{
		antiCSRF == $cookie || ($resyncCSRF.$formsLength = 0);

		while ($resyncCSRF.$formsLength < $forms.length) syncCSRF($forms[$resyncCSRF.$formsLength++]);

		return $cookie;
	}
}

resyncCSRF.$formsLength = 0;

onDOMLoaded = [];
onDOMLoaded.go = function()
{
	var $i, $pool, $script;

	resyncCSRF();

	if (window.scrollCntrl) scrollCntrl(), scrollCntrl = 0;

	if (document.removeChild)
	{
		$pool = document.getElementsByName('w$');
		$i = $pool.length;
		while ($i--) 'script' == ($script = $pool[$i]).tagName.toLowerCase() && $script.parentNode.removeChild($script);
		$script = 0;
	}

	$pool = onDOMLoaded;
	for ($i = 0; $i < $pool.length; ++$i) $pool[$i](), $pool[$i]=0;

	onDOMLoaded = [];
	onDOMLoaded.go = function() {};
}


if (window.Error)
	// This eval avoids a parse error with browsers not supporting exceptions.
	eval('try{document.execCommand("BackgroundImageCache",false,true)}catch(w){}');

w = function($baseAgent, $keys, $masterCIApID)
{
	$masterCIApID /= 1;

	var $document = document,

		$buffer = [],
		$inlineJs = 0,
		$includeSrc = '',
		$trustReferer = 0,
		$reloadRequest = 0,
		$reloadNoCache = 0,

		$WexecStack = [],
		$WexecLast = 0,

		$WobStack = [],
		$WobLast = 0,

		$i, $j, $loopIterator = [],

		a, d, v, g,
		$CIApID = $masterCIApID,

		r = {toString: function() {return g.__BASE__}},

		$lastInclude = '',
		$includeCache = {},
		$startTime,

		$masterBase = {g$__BASE__|js};

	if (!/safari|msie [0-5]\./i.test(navigator.userAgent) && !/(^|; )JS=1(; |$)/.test($document.cookie))
	{
		$document.cookie = 'JS=1; path=/; expires=' + new Date({$maxage|js}000+new Date()/1).toGMTString();
		0 || /(^|; )JS=1(; |$)/.test($document.cookie) || ($document.cookie = 'JS=1; path=/');
	}

	window.base = function($str, $noId, $master)
	{
		t($str) || ($str = '');

		if (!/^https?:\/\//.test($str))
		{
			$master = $master ? $masterBase : g.__BASE__;
			$noId = !$str || $noId;

			$str = (
				  0 == $str.indexOf('/')
				? $master.substr(0, $master.indexOf('/', 8))
				: $master
			) + $str;

			if (!$noId && '/' != $str.substr(-1)) $str += (-1 == $str.indexOf('?') ? '?' : '&amp;') + $masterCIApID;
		}

		return $str;
	}

/*
*		a : arguments
*		d : data, local root
*		v : data, local
*		v.$ : data, parent
*		g : get

*		w.k() : get agent's keys
*		w.w() : does document.write()
*		w.r() : flag the page for location.reload()
*		w.x() : loop construtor for data arrays
*		y() : loop construtor for numbers
*		z() : counter initialization and incrementation
*/

	function $echo($a)
	{
		t($a) && ($WobLast ? $WobStack[$WobLast] : $buffer).push($a);
	}

	function $include($inc, $args, $keys, $c, $i, $j)
	{
		if ($args)
		{
			if ($inc.indexOf('?')==-1) $inc += '?';
			$c = [];

			if ($keys)
			{
				antiCSRF && ($args.T$ = antiCSRF);

				if ($args.e$) for ($i in $args) $args[$i] = num(str($args[$i]), 1);
				else          for ($i in $args) $args[$i] = num(    $args[$i] , 1);

				for ($i=0; $i<$keys.length; ++$i)
					if (($j = $keys[$i]) && t($args[$j]))
						$c.push('&amp;' + eUC($j) + '=' + eUC(unesc($args[$j])));

				$c = $c.join('');

				if ($args.e$) $args.__URI__ += '?' + $c.substr(5);
				a = $args;

				$inc += $c;
				$c = 1;
			}
			else
			{
				w.k = function($id, $base, $agent, $__0__, $keys)
				{
					$base = esc($base).replace(/__/, g.__LANG__);
					$agent = esc($agent);

					$args.__0__ = $__0__;
					$__0__ = $__0__.split('/');
					for ($i = 0; $i < $__0__.length; ++$i) $args['__' + ($i+1) + '__'] = $__0__[$i];

					if ($base != g.__BASE__)
					{
						$CIApID = $id/1;

						$args.__DEBUG__ = g.__DEBUG__;
						$args.__LANG__ = g.__LANG__;
						$args.__BASE__ = $base;
						$args.__HOST__ = $base.substr(0, $base.indexOf('/', 8)+1);
						$args.__AGENT__ = $agent ? $agent + '/' : '';
						$args.__URI__ = $base + $agent;
						$args.e$ = 1;

						g = $args;
					}

					$include($base + '_?a$=' + $agent, $args, $keys, 1)
				}

				$inc += '&amp;k$=';
				$c = 0;
			}
		}

		$lastInclude = $c ? $inc : '';

		return $c && t($includeCache[$inc])
			? w($includeCache[$inc][0], $includeCache[$inc][1], $WexecLast)
			: ($includeSrc = $inc, !w.w());
	}

	w = function($context, $code, $WexecLastLimit)
	{
		'$baseAgent'; //Tells jsqueez that this var must not be overwritten
		$code = $code || [];
		$WexecLastLimit = $WexecLastLimit || 0;

		var $origContext,
			$pointer = 0,
			$arguments = a,
			$localCIApID = $CIApID,
			$localG = g,
			$bytecode = [

			// 0: pipe
			function($code)
			{
				var $i = $code[$pointer++], $j;

				if ($i)
				{
					$code[$pointer-1] = 0;

					$i = $i.split('.');
					$j = $i.length;
					while ($j--) $i[$j] = t(window['P$'+$i[$j]]) ? '' : ('.'+$i[$j]);

					$i = $i.join('');

					if ($i) return $include(g.__BASE__ + '_?p$=' + esc($i.substr(1)));
				}
			},

			// 1: agent
			function($code)
			{
				var $agent = $evalNext($code),
					$args = $evalNext($code),
					$keys = $code[$pointer++],
					$meta = $code[$pointer++],
					$data, $i, $j;

				<!-- IF g$__DEBUG__ -->
				if (!t($agent))
				{
					$i = '' + $code[$pointer-4];
					E('Undefined AGENT: ' + $i.substring(7+$i.indexOf('return '), $i.indexOf(';')), 1);
					return;
				}
				<!-- ELSE -->
				if (!t($agent)) return;
				<!-- END:IF -->

				if (t($agent, 'function'))
				{
					$agent = $agent();
					while ($j = $agent()) $data = $j;

					$agent = $data.a$;
					$data.k$ ? eval('$keys=['+$data.k$+']') : $keys = [];

					for ($i in $data) if (!/\$/.test($i)) $args[$i] = $data[$i];

					if ($data.r$) $meta = [$data.v$, $data.r$];
				}

				$agent = esc($agent);

				if (!$meta) $agent = g.__BASE__ + '_?t$=' + $agent;
				else
				{
					if ($meta > 1)
					{
					<!-- IF g$__DEBUG__ -->
						if (/^(\/|https?:\/\/)/.test($agent))
						{
							if (2 == $meta)
							{
								E('EXOAGENT (' + $agent + ') called with AGENT', 1);
								return;
							}

							$keys = 0;
						}
						else if (3 == $meta)
						{
							E('AGENT (' + $agent + ') called with EXOAGENT', 1);
							return;
						}
					<!-- ELSE -->
						if (/^(\/|https?:\/\/)/.test($agent))
						{
							if (2 == $meta) return;

							$keys = 0;
						}
					<!-- END:IF -->
					}
					else if (1 != $meta)
					{
						$CIApID = $meta[0]/1;

						$args.__DEBUG__ = g.__DEBUG__;
						$args.__LANG__ = g.__LANG__;
						$args.__BASE__ = esc($meta[1]).replace(/__/, $args.__LANG__);
						$args.__HOST__ = $args.__BASE__.substr(0, $args.__BASE__.indexOf('/', 8)+1);
						$args.__AGENT__ = $agent ? $agent + '/' : '';
						$args.__URI__ = $args.__BASE__ + $agent;
						$args.e$ = 1;

						g = $args;
					}

					$agent = $keys ? g.__BASE__ + '_?a$=' + $agent : base($agent, 1);
				}

				return $include($agent, $args, $keys, 1) ? 1 : -1;
			},

			// 2: echo
			function($code)
			{
				$echo( $code[$pointer++] );
			},

			// 3: eval echo
			function($code)
			{
				$echo( $evalNext($code) );
			},

			// 4: set
			function($code)
			{
				$WobStack[++$WobLast] = [];
			},

			// 5: endset
			function($code)
			{
				var $i = $code[$pointer++], $j;

				if (!$i) $i = a;
				else if ($i==1) $i = g;
				else
				{
					$j = $i - 1;
					$i = v;
					while (--$j) $i = $i.$;
				}

				$i[$code[$pointer++]] = num($WobStack[$WobLast--].join(''), 1);
			},

			// 6: jump
			function($code)
			{
				$pointer += $code[$pointer];
			},

			// 7: if
			function($code)
			{
				($evalNext($code) && ++$pointer) || ($pointer += $code[$pointer]);
			},

			// 8: loop
			function($code)
			{
				var $i = $evalNext($code);
				($i && (t($i, 'function') || ($i = y($i-0))) && $i()() && ++$pointer) || ($pointer += $code[$pointer]);
				$context = v;
			},

			// 9: next
			function($code)
			{
				($loopIterator() && ($pointer -= $code[$pointer])) || ++$pointer;
				$context = v;

				if (new Date - $startTime > 500) return $include($masterBase + 'js/x');
			}
		];

		if (!$WexecLastLimit)
		{
			$startTime = new Date;

			resyncCSRF();

			if (($i = $document.cookie.match(/(^|; )v\$=([0-9]+)(; |$)/)) && $i[2]-0 != $masterCIApID) w.r(), $code = [];
		}

		<!-- IF g$__DEBUG__ -->var DEBUG = $i = 0;<!-- END:IF -->

		if ($lastInclude && !$includeCache[$lastInclude])
		{
			$includeCache[$lastInclude] = [$context, $code];
			if ($context) for ($i in $context) $context[$i] = esc($context[$i]);

			<!-- IF g$__DEBUG__ -->
			DEBUG = $i ? 2 : 1;
			<!-- END:IF -->
		}

		if ($context) $origContext = $context.$ = v = $context;
		else $context = v;

		<!-- IF g$__DEBUG__ -->
		if (DEBUG) E({
			'Agent': dUC(('['+$lastInclude.substr(g.__BASE__.length + 2)).replace(/&(amp;)?/g, ', [').replace(/=/g, '] = ')),
			'Arguments': a,
			'Data': DEBUG-1 ? $context : ''
		});
		<!-- END:IF -->

		function $evalNext($code)
		{
			'function' == typeof $code[$pointer] || eval('$code[$pointer]=function(a,d,v,g,z,r){return ' + $code[$pointer] + '}');
			return $code[$pointer++](a, d, v, g, z, r);
		}

		$WexecStack[++$WexecLast] = function()
		{
			var $b = $bytecode, $c = $code, $codeLen = $c.length, $i;

			d = $origContext;
			a = $arguments;
			v = $context;

			$CIApID = $localCIApID;
			g = $localG;

			while (++$pointer <= $codeLen) if ($i = $b[$c[$pointer-1]]($c))
			{
				if (0 < $i) return 1;

				d = $origContext;
				a = $arguments;
				v = $context;

				$CIApID = $localCIApID;
				g = $localG;
			}

			$WexecStack[$WexecLast] = 0;
		};

		do if ($WexecStack[$WexecLast]()) return 1;
		while (--$WexecLast > $WexecLastLimit);

		if (!$WexecLast) return !w.w();
	}

	w.w = function()
	{
		// Any optimization to save some request here is likely to break IE ...

		var $src = $includeSrc,
			$content = $reloadRequest ? '' : $buffer.join(''),
			$offset = 0,
			$i = $content.search(/<\/script\b/i);

		$includeSrc = '';
		w.c = w;
		$buffer = [];

		while ($i>=0)
		{
			$inlineJs = 0;

			if ('>' == $content.charAt($offset + $i + 8) && $offset + $i != $content.length - 9)
			{
				$i += 9;
				$includeSrc = $src;
				w.c = w.w;
				$src = $masterBase + 'js/x';
				break;
			}

			$offset += $i + 8;
			$i = $content.substr($offset).search(/<\/script\b/i);
		}

		if ($i>=0) ;
		else if ($inlineJs) $i = 0;
		else
		{
			$i = $content.substr($offset).search(/<script\b/i);
			if ($i>=0) $inlineJs = 1;
		}

		if (0<=$i)
			$i += $offset,
			$buffer = [$content.substr($i)],
			$content = $content.substring(0, $i);

		$i = !!$src;
		$i || ($src = $masterBase + 'js/x');

		$src = '<script type="text/javascript" name="w$" src="' + $src + (0<=$src.indexOf('?') ? '&amp;' : '?') + 'v$=' + $CIApID + '"></script>';

		if ($i)
		{
			if ($trustReferer || /(^|; )T\$=1/.test($document.cookie)) $trustReferer = 1;
			else $src = '<script type="text/javascript" name="w$">document.cookie="R$="+eUC((""+location).replace(/#.*$/,""))+"; path=/"</script>' + $src;

			$document.write($content + $src);
		}
		else
		{
			// Memory leaks prevention
			w = r = y = z = w.c = w.k = w.w = w.r = w.x = $loopIterator = 0;

			if ($reloadRequest)
			{
				$document.close();
				$document = 0;
				location.reload($reloadNoCache);
			}
			else
			{
				w = {c: function()
				{
					$i = ($i = $document.cookie.match(/(^|; )v\$=([0-9]+)(; |$)/)) && $i[2]/1 != $masterCIApID;

					w = w.c = $document = 0;

					$i ? location.reload() : onDOMLoaded.go();
				}};

				$i = $content.search(/<\/body\b/i);
				if (0<=$i)
					$src += $content.substr($i),
					$content = $content.substr(0, $i);

				$document.write($content + $src);
				$document.close();
			}
		}
	}

	w.r = function($now, $noCache)
	{
		if ($masterBase != g.__BASE__) $document.cookie = 'cache_reset_id=' + $masterCIApID + '; path=/';
		$reloadRequest = true;
		$reloadNoCache = $reloadNoCache || !!$noCache;
		if ($now) $WexecLast = $WexecStack.length = 0;
	}

	w.x = function($data)
	{
		if (!$data[0]) return 0;

		var $block, $offset, $parent, $blockData, $parentLoop, $counter,

			$next = $data[1][0]

				? function($i, $j)
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

				: function() {return 0};

		function $loop()
		{
			return $parent = v,
				$parentLoop = $loopIterator,
				$counter = $offset = 0, $block = 1,
				$loopIterator = $next;
		}

		<!-- IF g$__DEBUG__ -->
		$loop.toString = function($a, $level)
		{
			if (!$level) return ''+$data[0];

			var $d = [], $e = 5;

			$a = $loop();
			while ($a())
			{
				if (!--$e)
				{
					v = v.$;
					$loopIterator = $parentLoop;
					$d.push('[...]');
					break;
				}

				v.$ = v.iteratorPosition = 0;
				$d.push(v);
			}

			E($d, 0, $level, 2);
			return '';
		}
		<!-- ELSE -->
		$loop.toString = function() {return ''+$data[0]};
		<!-- END:IF -->

		return $loop;
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

	function z($a, $b, $global, $i, $j)
	{
		$j = $global ? g : a;

		if (!t($j[$a])) $j[$a] = 0;
		$i = $j[$a]/1 || 0;
		$j[$a] += $b;

		return $i;
	}

	if (!resyncCSRF())
	{
		$i = '2';
		do $i = (Math.random()+$i).substr(2);
		while ($i.length < 33);
		$i = $i.substr(0, 33);

		$j = {$cookie_domain|js};

		$document.cookie = 'T$=' + $i + '; path=' + encodeURI({$cookie_path|js}) + ($j ? '; domain=' + encodeURI($j) : '');
		antiCSRF = /(^|; )T\$=2/.test($document.cookie) ? $i : '';
	}

	$j = location;

	g = parseurl($j.search.replace(/\+/g, '%20').substring(1), '&', /^amp;/);

	$j = ('' + $j).replace(/#.*$/, '');

	g.__DEBUG__ = {g$__DEBUG__|js};
	g.__HOST__ = {g$__HOST__|js};
	g.__LANG__ = {g$__LANG__|js};
	g.__BASE__ = $masterBase;
	g.__AGENT__ = $baseAgent ? esc($baseAgent) + '/' : '';
	g.__URI__ = esc($j);
	g.__REFERER__ = esc($document.referrer);

	if (t($baseAgent))
	{
		$j = dUC(esc($j).substr({g$__BASE__|length}+$baseAgent.length).split('?', 1)[0]).split('/');
		for ($i=0; $i<$j.length; ++$i) if ($j[$i]) $loopIterator[$loopIterator.length] = g['__'+($loopIterator.length+1)+'__'] = $j[$i];
		g.__0__ = $loopIterator.join('/');

		if (($i = $document.cookie.match(/(^|; )v\$=([0-9]+)(; |$)/)) && $i[2]/1 != $masterCIApID) w(0, [3, 'w(w.r())']);
		else

		/* Block load, 2 steps : generating, then displaying. * /
		w(0, [4, 1, '$baseAgent', 'g', $keys, 1, 5, 1, 'b', 3, 'g.b']);
		/**/

		/* Dynamic load, 1 step : generating and displaying at the same time. */
		w(0, [1, '$baseAgent', 'g', $keys, 1]);
		/**/
	}
}

function loadW($window)
{
	$window = window;

	if ($window.encodeURI)
	{
		$window.dUC = decodeURIComponent;
		$window.eUC = encodeURIComponent;

		(function($scrollPos)
		{
			if ($scrollPos = location.hash.match(/@([0-9]+),([0-9]+)$/))
			{
				($window.scrollCntrl = function()
				{
					var $body = document.documentElement || document.body,
						$left = Math.min($scrollPos[1], $body.scrollWidth),
						$top  = Math.min($scrollPos[2], $body.scrollHeight);

					$body && scrollTo($left, $top);

					if ($left != $body.scrollLeft || $top != $body.scrollTop) setTimeout('scrollCntrl&&scrollCntrl()', 100);
				})();
			}
		})();

		$window.a ? w(a[0], a[1], a[2]) : w();
	}
	else document.write('<script type="text/javascript" src="' + {g$__BASE__|js} + 'js/compat"></script>');
}

function P$base($string, $noId)
{
	return base(str($string), $noId);
}

loadW();
