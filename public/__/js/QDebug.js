/**************************************************************************
 *
 *   Copyright : (C) 2007 Nicolas Grekas. All rights reserved.
 *   Email     : p@tchwork.org
 *   License   : http://www.gnu.org/licenses/agpl.txt GNU/AGPL
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Affero General Public License as
 *   published by the Free Software Foundation, either version 3 of the
 *   License, or (at your option) any later version.
 *
 **************************************************************************/

function t($v, $type)
{
	return $type ? (typeof $v == $type) : (typeof $v != 'undefined');
}

function E($v, $warn, $max_depth, $level, $expand)
{
	var $startTime = new Date/1,
		$deltaTime = $startTime - E.lastTime,
		$key;

	$max_depth = t($max_depth) ? $max_depth : E.max_depth;
	$level = $level || 0;
	$expand = $expand || 0;

	if (t($v))
	{
		function o($str, $r)
		{
			if (!t($r) || $r>0)
			{
				E.buffer.push($str);
				$r = $r || 0;
				for (var $i=0; $i<$r; ++$i) E.buffer.push($str);
			}
		}

		function p($str)
		{
			return $str.toString(10, $level).replace(/</g, '&lt;').replace(/>/g, '&gt;');
		}

		if (0 == $level) o(($warn ? '<script type="text/javascript">focus();L=opener||parent;L=L&&L.document.getElementById(\'debugLink\');L=L&&L.style;if(L){L.backgroundColor=\'red\';L.fontSize=\'18px\'}<\/script><pre style="color:red;font-weight:bold">' : '<pre>') + $deltaTime + ' ms : ');

		if (t($v, 'object') || t($v, 'array'))
		{
			if ($max_depth && $level>=$max_depth) return o('### Max Depth Reached ###\n');

			if ($level) o('<a href="#" onclick="return parent.QDebug_toggle(document, ' + (++E.counter) + ')">');
			o(t($v, 'object') ? 'Object\n' : 'Array\n');
			if ($level) o('</a><span id="QDebugId' + E.counter + '"'+($expand && !--$expand ? '' : ' style="display: none;"')+'>');
			o(' ', 8*$level);
			o('(\n');
			for ($key in $v) if (!E.hiddenList[$key])
			{
				o(' ',  8*$level);
				o('    ['+p($key)+'] => ');
				E($v[$key], 0, $max_depth, $level+1, $expand);
			}
			o(' ', 8*$level);
			o(')\n\n');
			if ($level) o('</span>');
		}
		else o(p($v)+'\n');

		if (0 == $level) o('</pre>');
	}

	if (0 == $level)
	{
		E.startTime += (E.lastTime = new Date/1) - $startTime;

		return $deltaTime;
	}
}

E.max_depth = 5;
E.buffer = [];
E.hide = function($key)
{
	if (!t($key, 'string')) for (var $i in $key) if (t($key[$i], 'string')) E.hiddenList[$key[$i]] = true;
	else E.hiddenList[$key] = true;
}

E.hiddenList = {
	'_AdblockData' : true,
	'ownerDocument' : true,
	'top' : true,
	'parent' : true,
	'parentNode' : true,
	'document' : true
};
E.lastTime = E.startTime = new Date/1;
E.counter = 0;
QDebug_toggle = function($d, $e)
{
	$e = $d.getElementById('QDebugId' + $e).style;
	$e.display = 'none' == $e.display ? '' : 'none';
	return false;
}
