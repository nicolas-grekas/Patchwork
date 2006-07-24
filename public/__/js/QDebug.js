function t($v, $type)
{
	return $type ? (typeof $v == $type) : (typeof $v != 'undefined');
}

function E($v, $max_depth, $level, $expand)
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

		if (0 == $level) o('<pre>' + $deltaTime + ' ms : ');

		if (t($v, 'object') || t($v, 'array'))
		{
			if ($max_depth && $level>=$max_depth) return o('### Max Depth Reached ###\n');

			if ($level) o('<a href="#" onclick="return opener.QDebug_toggle(document, ' + (++E.counter) + ')">');
			o(t($v, 'object') ? 'Object\n' : 'Array\n');
			if ($level) o('</a><span id="QDebugId' + E.counter + '"'+($expand && !--$expand ? '' : ' style="display: none;"')+'>');
			o(' ', 8*$level);
			o('(\n');
			for ($key in $v) if (!E.hiddenList[$key])
			{
				o(' ',  8*$level);
				o('    ['+p($key)+'] => ');
				E($v[$key], $max_depth, $level+1, $expand);
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
		_____ += (E.lastTime = new Date/1) - $startTime;

		return $deltaTime;
	}
}

E.max_depth = 5;
E.buffer = [];
E.hide = function($key)
{
	if (!t($key, 'string')) for (var $i in $key) E.hiddenList[$key[$i]] = true;
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
E.lastTime = _____;
E.counter = 0;
QDebug_toggle = function($d, $e)
{
	$e = $d.getElementById('QDebugId' + $e).style;
	$e.display = 'none' == $e.display ? '' : 'none';
	return false;
}
