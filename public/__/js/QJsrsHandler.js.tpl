$win = window;

$decodeURIComponent = $win.decodeURIComponent || function($string)
{
	var $dec = '',
		$len = $string.length,
		$i = 0,
		$c,
		$s = String.fromCharCode;

	function $nextCode() {return parseInt('0x' + $string.substr(++$i, 2, $i+=2)) - 128;}

	while ($i < $len)
	{
		if ($string.charAt($i) != '%') $dec += $string.charAt($i++);
		else
		{
			$c = $nextCode();

			$dec += $s( $c < 0
				? $c + 128
				: (
					$c < 96
					? ($c-64<<6)+$nextCode()
					: (
						$c < 112
						? (($c-96<<6) + $nextCode()<<6) + $nextCode()
						: ((($c-112<<6) + $nextCode()<<6) + $nextCode()<<6) + $nextCode()
					)
				)
			);
		}
	}

	return $dec;
}

$encodeURIComponent = $win.encodeURIComponent || function($string)
{
	var c, s, i = 0, $enc = '', $preserved = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_.!~*'()";

	while (i<$string.length)
	{
		c = $string.charCodeAt(i++);

		if (c>=56320 && c<57344) continue;

		if (c>=55296 && c<56320)
		{
			if (i>=$string.length) continue;
			s = $string.charCodeAt(i++);
			if (s<56320 || c>=56832) continue;
			c = (c-55296<<10)+s+9216;
		}

		s = String.fromCharCode;
		$enc += c<128
			? s(c)
			: (
				c<2048
				? s(192+(c>>6),128+(c&63))
				: (
					c<65536
					? s(224+(c>>12),128+(c>>6&63),128+(c&63))
					: s(240+(c>>18),128+(c>>12&63),128+(c>>6&63),128+(c&63))
				)
			);
	}

	$string = '';

	for (i = 0; i<$enc.length; ++i)
		$string += $preserved.indexOf($enc.charAt(i))==-1
			? '%'+$enc.charCodeAt(i).toString(16).toUpperCase()
			: $enc.charAt(i);

	return $string;
}

function $encode($str)
{
	return $encodeURIComponent('' + $str).replace(
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

function $decode($str)
{
	return $decodeURIComponent(('' + $str).replace(/_/g, '%'));
}

$glue = 'Z_Y';
$name = $win.name.split($glue);

if (3 == $name.length)
{
	$win.name += $glue + $encode(q);
	location.replace($decode($name[2]));
}
else
{
	if (4 == $name.length)
	{
		$win.name = $decode($name[1]);
		q = $decode($name[3]);
	}

	if (typeof $win.q != 'undefined') parent.loadQJsrs($win, q);
	else
	{
		q = parent.loadQJsrs($win).q;

		if (q && q.length)
		{
			if (!q[4]) $win.name = $glue + $encode($win.name) + $glue + $encode(location);

			if (q[3])
			{
				$document = document;
				$value = q[2];

				$form = '<form accept-charset="UTF-8" method="post">';
				for ($i in $value) $form += '<input />';
				$document.write($form + '</form>');

				onload = function()
				{
					$form = $document.forms[0];
					$form.action = q[0];

					$document = q.length = 0;
					for ($i in $value)
						$elt = $form[$document++],
						$elt.name = $i,
						$elt.value = $value[$i],
						++$i;

					$form.submit();
				}
			}
			else location.replace(q[0] + q[1]);
		}
	}
}
