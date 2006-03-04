function E($v, $max_depth, $level, $expand)
{
	$max_depth = typeof $max_depth != 'undefined' ? $max_depth : E.max_depth;
	$level = $level || 0;
	$expand = $expand || 0;

	function o($str, $r)
	{
		if (typeof $r=='undefined' || $r>0)
		{
			E.buffer += $str;
			$r = $r || 0;
			for (var $i=0; $i<$r; $i++) E.buffer += $str;
		}
	}

	function p($str)
	{
		return $str.toString(10, $level).replace(/</g, '&lt;').replace(/>/g, '&gt;');
	}

	if ($level == 0)
	{
		var $startTime = new Date;

		o('<pre>' + ($startTime - E.lastTime) + ' ms : ');
	}
	
	if (typeof $v=='object' || typeof $v=='array')
	{
		if ($max_depth && $level>=$max_depth)
		{
			o('### Max Depth Reached ###\n');
			return $v;
		}

		if ($level) o('<a href="#" onclick="return opener.QDebug_toggle(document, ' + (++E.counter) + ')">');
		o(typeof $v=='object' ? 'Object\n' : 'Array\n');
		if ($level) o('</a><span id="QDebugId' + E.counter + '" style="display:'+($expand && !--$expand ? '' : 'none')+'">');
		o(' ', 8*$level);
		o('(\n');
		for (var $key in $v) if (!E.hiddenList[$key])
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
	
	if ($level==0)
	{
		o('</pre>');
		_____ += (E.lastTime = new Date) - $startTime;
	}

	return $v;
}

E.max_depth = 5;
E.buffer = '';
E.hide = function($key)
{
	if (typeof $key!='string') for (var $i in $key) E.hiddenList[$key[$i]] = true;
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
