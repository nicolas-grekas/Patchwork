{*/**************************************************************************
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
 **************************************************************************/*}
/*
* eUC
* dUC
* str
* num
* esc
* unesc
* parseurl
* onDOMLoaded
* setboard
* topwin
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
	if (!$form)
	{
		$form = document.forms;
		$form = $form[$form.length - 1];
	}

	if ('post' == $form.method.toLowerCase())
	{
		if ($form.action.indexOf({g$__HOME__|js})) return;

		if ($form.T$) $form.T$.value = antiCSRF;
		else if ($form.firstChild)
		{
			var $a = document.createElement('input');

			$a.type = 'hidden';
			$a.name = 'T$';
			$a.value = antiCSRF;

			$form.insertBefore($a, $form.firstChild);
		}
		else $form.innerHTML += '<input type="hidden" name="T$" value="' + antiCSRF + '" />';
	}
}

onDOMLoaded = [];
onDOMLoaded.go = function()
{
	var $document = document, $i, $pool, $script;

	if ($document.removeChild)
	{
		$pool = $document.getElementsByName('w$');
		$i = $pool.length;
		while ($i--) 'script' == ($script = $pool[$i]).tagName.toLowerCase() && $script.parentNode.removeChild($script);
		$script = 0;
	}

	$pool = onDOMLoaded;
	for ($i = 0; $i < $pool.length; ++$i) $pool[$i](), $pool[$i]=0;

	$document = 0;

	onDOMLoaded = [];
	onDOMLoaded.go = function() {};
}


/*
* Set a board variable
*/
function setboard($name, $value, $window)
{
	if (t($name, 'object')) for ($value in $name) setboard($value, $name[$value], $window);
	else
	{
		$window = $window || topwin;

		function $escape($str)
		{
			return eUC(''+$str).replace(
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

		$name = '_K' + $escape($name) + '_V';

		var $varIdx = $window.name.indexOf($name),
			$varEndIdx;

		if ($varIdx>=0)
		{
			$varEndIdx = $window.name.indexOf('_K', $varIdx + $name.length);
			$window.name = $window.name.substring(0, $varIdx) + ( $varEndIdx>=0 ? $window.name.substring($varEndIdx) : '' );
		}

		$window.name += $name + $escape($value);
	}
}


if ((topwin = window).Error)
	// This eval avoids a parse error with browsers not supporting exceptions.
	eval('try{while(((w=topwin.parent)!=topwin)&&t(w.name))topwin=w}catch(w){}try{document.execCommand("BackgroundImageCache",false,true)}catch(w){}');

w = function($homeAgent, $keys, $masterCIApID)
{
	$masterCIApID /= 1;

	var $document = document,

		$buffer = [],
		$formsLength = 0,
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

		r = {toString: function() {return g.__HOME__}},

		$lastInclude = '',
		$includeCache = {},
		$startTime,
		$maxRlevel = 100,
		$Rlevel = $maxRlevel,
		
		$masterHome = {g$__HOME__|js};

	if (!/safari|msie [0-5]\./i.test(navigator.userAgent) && !/(^|; )JS=1(; |$)/.test($document.cookie)) $document.cookie = 'JS=1; path=/; expires=Mon, 18 Jan 2038 00:00:00 GMT';

	window.home = function($str, $master, $noId)
	{
		if (!/^https?:\/\//.test($str))
		{
				$master = $master ? $masterHome : g.__HOME__;

				$str = (
					0 == $str.indexOf('/')
					? $master.substr(0, $master.indexOf('/', 8))
					: $master
				) + $str;

			if (!$noId) $str += (-1 == $str.indexOf('?') ? '?' : '&amp;') + $masterCIApID;
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
			$c = '';

			if ($keys)
			{
				$args.T$ = antiCSRF;

				if ($args.e$) for ($i in $args) $args[$i] = num(str($args[$i]), 1);
				else          for ($i in $args) $args[$i] = num(    $args[$i] , 1);

				for ($i=0; $i<$keys.length; ++$i)
					if (($j = $keys[$i]) && t($args[$j]))
						$c += '&amp;' + eUC($j) + '=' + eUC(unesc($args[$j]));

				if ($args.e$) $args.__URI__ += '?' + $c.substr(5);
				a = $args;
				$include($inc + $c);
			}
			else
			{
				w.k = function($id, $home, $agent, $__0__, $keys)
				{
					$home = esc($home).replace(/__/, g.__LANG__);
					$agent = esc($agent);

					$args.__0__ = $__0__;
					$__0__ = $__0__.split('/');
					for ($i = 0; $i < $__0__.length; ++$i) $args['__' + ($i+1) + '__'] = $__0__[$i];

					if ($home != g.__HOME__)
					{
						$CIApID = $id/1;

						$args.__DEBUG__ = g.__DEBUG__;
						$args.__LANG__ = g.__LANG__;
						$args.__HOME__ = $home;
						$args.__HOST__ = $home.substr(0, $home.indexOf('/', 8)+1);
						$args.__AGENT__ = $agent ? $agent + '/' : '';
						$args.__URI__ = $home + $agent;
						$args.e$ = 1;

						g = $args;
					}

					$include($home + '_?a$=' + $agent, $args, $keys)
				}

				$include($inc + '&amp;k$=', 0, 0, 1);
			}
		}
		else
		{
			$lastInclude = $c ? '' : $inc;

			if (t($includeCache[$inc]) && --$Rlevel) w($includeCache[$inc][0], $includeCache[$inc][1], 1);
			else
				$includeSrc = $inc,
				w.w();
		}
	}

	w = function($context, $code, $fromCache)
	{
		$homeAgent; //This is here for jsquiz to work well
		$code = $code || [];

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

					if ($i) return $include(g.__HOME__ + '_?p$=' + esc($i.substr(1)), 0, 0, 1), 1;
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

				if (!$meta) $agent = g.__HOME__ + '_?t$=' + $agent;
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
						$args.__HOME__ = esc($meta[1]).replace(/__/, $args.__LANG__);
						$args.__HOST__ = $args.__HOME__.substr(0, $args.__HOME__.indexOf('/', 8)+1);
						$args.__AGENT__ = $agent ? $agent + '/' : '';
						$args.__URI__ = $args.__HOME__ + $agent;
						$args.e$ = 1;

						g = $args;
					}

					$agent = $keys ? g.__HOME__ + '_?a$=' + $agent : home($agent, 0, 1);
				}

				return $include($agent, $args, $keys), 1;
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

				if (new Date - $startTime > 500) return $include($masterHome + 'js/x', 0, 0, 1), 1;
			}
		];

		if (!$fromCache)
		{
			$startTime = new Date;
			$Rlevel = $maxRlevel;
			while ($formsLength < $document.forms.length) syncCSRF($document.forms[$formsLength++]);
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
			'Agent': dUC(('['+$lastInclude.substr(g.__HOME__.length + 2)).replace(/&(amp;)?/g, ', [').replace(/=/g, '] = ')),
			'Arguments': a,
			'Data': DEBUG-1 ? $context : ''
		});
		<!-- END:IF -->

		function $evalNext($code)
		{
			'function' == typeof $code[$pointer] || eval('$code[$pointer]=function(a,d,v,g){return ' + $code[$pointer] + '}');
			return $code[$pointer++](a, d, v, g);
		}

		($WexecStack[++$WexecLast] = function()
		{
			var $b = $bytecode, $c = $code, $codeLen = $c.length;

			d = $origContext;
			a = $arguments;
			v = $context;

			$CIApID = $localCIApID;
			g = $localG;

			while (++$pointer <= $codeLen) if ($b[$c[$pointer-1]]($c)) return;

			$WexecStack[$WexecLast] = 0;

			if (--$WexecLast) $WexecStack[$WexecLast]();
			else w.w();
		})();
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
				$src = $masterHome + 'js/x';
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

		if ($src)
		{
			$src = '<script type="text/javascript" name="w$" src="' + $src + (0<=$src.indexOf('?') ? '&amp;' : '?') + 'v$=' + $CIApID + '"></script>';

			if ($trustReferer || /(^|; )T$=1/.test($document.cookie)) $trustReferer = 1;
			else $src = '<script type="text/javascript" name="w$">document.cookie="R$="+eUC((""+location).replace(/#.*$/,""))+"; path=/"</script>' + $src;

			$document.write($content + $src);
		}
		else
		{
			if (!$reloadRequest)
			{
				$src = '<script type="text/javascript" name="w$">(function(){var i='
					+ $formsLength
					+ ',f=document.forms;while(i-f.length)syncCSRF(f[i++])})();onDOMLoaded.go()</script>';

				$i = $content.search(/<\/body\b/i);
				if (0<=$i)
					$src += $content.substr($i),
					$content = $content.substr(0, $i);
			}

			$document.write($content + $src);
			$document.close();

			w = $document = r = y = z = w.k = w.w = w.r = w.x = $loopIterator = 0; // Memory leaks prevention

			if ($reloadRequest) location.reload($reloadNoCache);
		}
	}

	w.r = function($now, $noCache)
	{
		if ($masterHome != g.__HOME__) $document.cookie = 'cache_reset_id=' + $masterCIApID + '; path=/';
		$reloadRequest = true;
		$reloadNoCache = $reloadNoCache || !!$noCache;
		if ($now)
		{
			do $WexecStack[$WexecLast] = 0;
			while (--$WexecLast);
		}
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

	if ($i = $document.cookie.match(/(^|; )T\$=([0-9a-zA-Z]+)/)) $i = $i[2];
	else
	{
		$i = '0';
		do $i = (Math.random()+$i).substr(2);
		while ($i.length < 33);
		$i = $i.substr(0, 33);

		$j = $masterHome.replace(
			/\?.*$/              , '' ).replace(
			/^https?:\/\/[^\/]*/i, '' ).replace(
			/\/[^\/]+$/          , '/'
		);

		$document.cookie = 'T$=' + $i + '; path=' + encodeURI($j);
	}

	antiCSRF = $i;

	$j = location;

	g = parseurl($j.search.replace(/\+/g, '%20').substring(1), '&', /^amp;/);
	g.__DEBUG__ = {g$__DEBUG__|js};
	g.__HOST__ = {g$__HOST__|js};
	g.__LANG__ = {g$__LANG__|js};
	g.__HOME__ = $masterHome;
	g.__AGENT__ = $homeAgent ? esc($homeAgent) + '/' : '';
	g.__URI__ = esc(''+$j);
	g.__REFERER__ = esc($document.referrer);

	if (t($homeAgent))
	{
		$j = dUC(esc(''+$j).substr({g$__HOME__|length}+$homeAgent.length).split('?', 1)[0]).split('/');
		for ($i=0; $i<$j.length; ++$i) if ($j[$i]) $loopIterator[$loopIterator.length] = g['__'+($loopIterator.length+1)+'__'] = $j[$i];
		g.__0__ = $loopIterator.join('/');

		/* Block load, 2 steps : generating, then displaying. * /
		w(0, [4, 1, '$homeAgent', 'g', $keys, 1, 5, 1, 'b', 3, 'g.b']);
		/**/

		/* Dynamic load, 1 step : generating and displaying at the same time. */
		w(0, [1, '$homeAgent', 'g', $keys, 1]);
		/**/
	}
}

function loadW()
{
	var $window = window, $board = topwin.name.indexOf('_K'), $a = location;

	if ($window.encodeURI)
	{
		$window.dUC = decodeURIComponent;
		$window.eUC = encodeURIComponent;

		$window.BOARD = {};

		if (0 <= $board)
		{
			$board = parseurl(
				topwin.name.substr($board).replace(
					/_K/g, '&').replace(
					/_V/g, '=').replace(
					/_/g , '%')
				, '&'
			);

			$a = $a.protocol + ':' + $a.hostname;
			
			if ($board.$ != $a) topwin.name = '', setboard('$', $a);
			else $window.BOARD = $board;
		}

		$window.a ? w(a[0], a[1], a[2]) : w();
	}
	else document.write('<script type="text/javascript" src="' + {g$__HOME__|js} + 'js/compat"></script>');
}

function P$home($string, $noId)
{
	return home(str($string), 0, $noId);
}

loadW();
