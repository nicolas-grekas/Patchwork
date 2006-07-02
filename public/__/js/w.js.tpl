/*
* eUC
* dUC
* str
* num
* esc
* unesc
* parseurl
* loadPng
* addOnload
* setboard
* topwin
* _BOARD
* _COOKIE
*/

footerHtml = '';

if (antiXSJ = document.cookie.match(/^(.*; )?T\$=([0-9A-Z]+)/i)) antiXSJ = antiXSJ[2];

document.cookie = 'JS=0; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';

if (window.Error && navigator.userAgent.indexOf('Safari') < 0)
	document.cookie = 'JS=1; expires=Sun, 17-Jan-2038 19:14:07 GMT; path=/';

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
	var $i, $j;
	$array = $array || {};

	$param = $param.split($delim);
	for ($i in $param)
	{
		if ($rx) $param[$i] = $param[$i].replace($rx, '');
		$delim = $param[$i].indexOf('=');
		if ( $delim>0 ) $array[ dUC( $param[$i].substring(0, $delim) ) ] = num(esc(dUC( $param[$i].substring($delim+1) )), 1);
	}

	return $array;
}

function loadPng($this)
{
	$this = $this || this;
	var $src = $this.src, $width = $this.width, $height = $this.height;
	if (/\.png$/i.test($src))
	{
		$this.style.width  = ($this.offsetWidth  || $this.width ) + 'px';
		$this.style.height = ($this.offsetHeight || $this.height) + 'px';

		$this.src = home('img/blank.gif', 1);
		$this.style.filter = 'progid:DXImageTransform.Microsoft.AlphaImageLoader(src="'+$src+'",sizingMethod="scale")';
	}
}

function addOnload($function)
{
	var $p = addOnload.p;
	$p[$p.length] = $function;
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


addOnload.p = [];
if ((topwin = window).Error)
	// This eval avoids a parse error with browsers not supporting exceptions.
	eval('try{while(((w=topwin.parent)!=topwin)&&t(w.name))topwin=w}catch(w){}');


w = function($homeAgent, $keys, $masterCIApID)
{
	$masterCIApID /= 1;

	var $document = document,

		$buffer = '',
		$closeDoc = 0,

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
		
		$masterHome = {g$__HOME__|js};

		window.home = function($str, $master)
		{
			$master = $master ? $masterHome : g.__HOME__;

			return (
				/^https?:\/\//.test($str)
				? ''
				: (
					0 == $str.indexOf('/')
					? $master.substr(0, $master.indexOf('/', 8))
					: $master
				)
			) + $str;
		}

/*
*		a : arguments
*		d : data, local root
*		v : data, local
*		v.$ : data, parent
*		g : get

*		w.x() : loop construtor for data arrays
*		y() : loop construtor for numbers
*		z() : counter initialization and incrementation
*/

	w = function($context, $code)
	{
		if (!t($context)) return $homeAgent; //"$homeAgent" is here for jsquiz to work well

		var $pointer = 0, $arguments = a, $localCIApID = $CIApID, $localG = g;

		<!-- IF g$__DEBUG__ -->var DEBUG = $i = 0;<!-- END:IF -->

		if ($lastInclude && !$includeCache[$lastInclude])
		{
			if (!antiXSJ && ($i = $document.cookie.match(/^(.*; )?T\$=([0-9A-Z]+)/i)))
			{
				antiXSJ = $i[2];
				$i = $document.getElementsByName('T$');
				for ($j in $i) $i[$j].value = antiXSJ;
			}

			$includeCache[$lastInclude] = [$context, $code];
			if ($context) for ($i in $context) $context[$i] = esc($context[$i]);

			<!-- IF g$__DEBUG__ -->
			DEBUG = window.ScriptEngine ? 0 : ($i ? 2 : 1);
			<!-- END:IF -->
		}

		if ($context) d = $context.$ = v = $context;
		else $context = v;

		<!-- IF g$__DEBUG__ -->
		if (DEBUG) E({
			'Agent': dUC(('['+$lastInclude.substr(g.__HOME__.length + 2)).replace(/&(amp;)?/g, ', [').replace(/=/g, '] = ')),
			'Arguments': a,
			'Data': DEBUG-1 ? $context : ''
		});
		<!-- END:IF -->

		function $execute()
		{
			$pointer || ($WexecStack[++$WexecLast] = $execute);
			a = $arguments;
			v = $context;

			$CIApID = $localCIApID;
			g = $localG;

			while ($code[$pointer]>='') switch ($code[$pointer++])
			{
				case 0: // pipe
					$i = $code[$pointer++].split('.');
					$j = $i.length;
					while ($j--) $i[$j] = t(window['P$'+$i[$j]]) ? '' : ('.'+$i[$j]);

					$i = $i.join('');

					if ($i) return $include(g.__HOME__ + '_?p$=' + esc($i.substr(1)), 0, 0, 1);
					break;

				case 1: // agent
					var $agent = $evalNext(),
						$args = $evalNext(),
						$keys = $code[$pointer++],
						$meta = $code[$pointer++],
						$data;

					<!-- IF g$__DEBUG__ -->
					if (!t($agent))
					{
						E('AGENT is undefined: ' + $code[$pointer-4]);
						break;
					}
					<!-- ELSE -->
					if (!t($agent)) break;
					<!-- END:IF -->

					if (t($agent, 'function'))
					{
						$agent = $agent();
						while ($j = $agent()) $data = $j;

						$agent = $data.a$;
						eval('$keys='+$data.k$);

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
									E('EXOAGENT (' + $agent + ') called with AGENT');
									break;
								}

								$keys = 0;
							}
							else if (3 == $meta)
							{
								E('AGENT (' + $agent + ') called with EXOAGENT');
								break;
							}
						<!-- ELSE -->
							if (/^(\/|https?:\/\/)/.test($agent))
							{
								if (2 == $meta) break;

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

						$agent = $keys ? g.__HOME__ + '_?a$=' + $agent : home($agent);
					}

					return $include($agent, $args, $keys);

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
					($i && (t($i, 'function') || ($i = y($i-0))) && $i()() && ++$pointer) || ($pointer += $code[$pointer]);
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

		function $include($inc, $args, $keys, $c)
		{
			if ($args)
			{
				if ($inc.indexOf('?')==-1) $inc += '?';
				$c = '';

				if ($keys)
				{
					if (!antiXSJ)
					{
						$i = (''+Math.random()).substr(2);
						$j = $masterHome.replace(
							/\?.*$/              , '' ).replace(
							/^https?:\/\/[^\/]*/i, '' ).replace(
							/\/[^\/]+$/          , '/'
						);

						$document.cookie = 'T$=' + $i + '; path=' + encodeURI($j);
					}

					$args.T$ = antiXSJ || $i;

					if ($args.e$) for ($i in $args) $args[$i] = num(str($args[$i]), 1);
					else          for ($i in $args) $args[$i] = num(    $args[$i] , 1);

					for ($i=0; $i<$keys.length; ++$i)
						if (($j = $keys[$i]) && t($args[$j]))
							$c += '&amp;' + eUC($j) + '=' + eUC(unesc($args[$j]));

					if ($args.e$) $args.__URI__ += '?' + $c.substr(5);
					a = $args;
					$include($inc + $c + '&amp;v$=' + $CIApID);
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

				if (t($includeCache[$inc])) w($includeCache[$inc][0], $includeCache[$inc][1]);
				else
					$buffer += '<script type="text/javascript" class="w" src="' + $inc + '"></script >',
					w.f();
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
			$content = $buffer.substring(0, $i) + '<script type="text/javascript" class="w" src="' + $masterHome + 'js/x"></script>', // Any optimization to save some request here is likely to break IE ...
			$buffer = $buffer.substr($i);

		$document.write($content);

		if ($i<0 && $closeDoc)
			$document.close(),
			w = $document = r = y = z = w.k = w.f = w.r = w.x = 0;
	}

	w.r = function()
	{
		if ($masterHome != g.__HOME__) $document.cookie = 'cache_reset_id=' + $masterCIApID + '; path=/';
		location.reload();
	}

	w.x = function($data)
	{
		if (!$data[0]) return 0;

		var $block, $offset, $parent, $blockData, $parentLoop, $counter,

			$next = $data[1][0]

				? function()
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

	function z($a, $b, $global)
	{
		$j = $global ? g : a;

		if (!t($j[$a])) $j[$a] = 0;
		$i = $j[$a]/1 || 0;
		$j[$a] += $b;

		return $i;
	}

	$j = location;

	g = parseurl($j.search.replace(/\+/g, '%20').substring(1), '&', /^amp;/);
	g.__DEBUG__ = {g$__DEBUG__|js};
	g.__HOST__ = {g$__HOST__|js};
	g.__LANG__ = {g$__LANG__|js};
	g.__HOME__ = $masterHome;
	g.__AGENT__ = $homeAgent ? esc($homeAgent) + '/' : '';
	g.__URI__ = esc(''+$j);

	if (t($homeAgent))
	{
		$j = dUC(esc(''+$j).substr({g$__HOME__|length}+$homeAgent.length).split('?', 1)[0]).split('/');
		for ($i=0; $i<$j.length; ++$i) if ($j[$i]) $loopIterator[$loopIterator.length] = g['__'+($loopIterator.length+1)+'__'] = $j[$i];
		g.__0__ = $loopIterator.join('/');

		w(0, [1, '$homeAgent', 'g', $keys, 1]);
	}
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
	var $window = window, $board = topwin.name.indexOf('_K'), $a = location;

	if ($window.encodeURI)
	{
		$window.dUC = decodeURIComponent;
		$window.eUC = encodeURIComponent;

		$window._BOARD = {};

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
			else $window._BOARD = $board;
		}

		$window.a ? w(a[0], a[1], a[2]) : w();
	}
	else document.write('<script type="text/javascript" src="js/compat"></script>');
}

function P$home($string)
{
	return home( str($string) );
}

loadW();
