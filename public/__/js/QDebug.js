function E($v, $max_depth, $level)
{
	$max_depth = $max_depth>='' ? $max_depth : 5;
	$level = $level || 0;

	if ($level == 0)
	{
		var $startTime = new Date;

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
			return typeof $str=='string' ? $str.replace(/</g, '&lt;').replace(/>/g, '&gt;') : $str;
		}


		o('<pre>' + ($startTime - E.lastTime) + ' ms : ');
	}
	
	if (typeof $v=='object' || typeof $v=='array')
	{
		if ($max_depth && $level>=$max_depth) return o('### Max Depth Reached ###\n');
		
		o(typeof $v=='object' ? 'Object\n' : 'Array\n');
		o(' ', 8*$level);
		o('(\n');
		for (var $key in $v) if (typeof $v[$key]!='function' && !E.hiddenList[$key])
		{
			o(' ',  8*$level);
			o('    ['+p($key)+'] => ');
			E($v[$key], $max_depth, $level+1);
		}
		o(' ', 8*$level);
		o(')\n\n');
	}
	else o(p($v)+'\n');
	
	if ($level==0)
	{
		o('</pre>');
		_____ += (E.lastTime = new Date) - $startTime;
	}
}

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
