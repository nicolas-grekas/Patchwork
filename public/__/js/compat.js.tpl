/*

Usefull functions, existing in recent browsers, but missing in IE<=5.0

*/

document.getElementById = document.getElementById || function($id) {return document.all[$id];}
document.getElementsByName = document.getElementsByName || function($id) {return document.all[$id];}
document.getElementsByTagName = document.getElementsByTagName || function($tagName)
{
	return '*' == $tagName ? document.all : document.all.tags($tagName);
}

decodeURI = window.decodeURI || function($string)
{
	return decodeURIComponent($string.replace(/%(2[46BCF]|3[ADF]|40)/gi, '%25$1'));
}

decodeURIComponent = window.decodeURIComponent || function($string)
{
	var $dec = [],
		$len = $string.length,
		$i = 0,
		$c,
		$s = String.fromCharCode;

	function $nextCode() {return parseInt('0x' + $string.substr(++$i, 2, $i+=2)) - 128;}

	while ($i < $len)
	{
		if ($string.charAt($i) != '%') $dec.push($string.charAt($i++));
		else
		{
			$c = $nextCode();

			$dec.push($s( $c < 0
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
			));
		}
	}

	return $dec.join('');
}

encodeURI = window.encodeURI || function($string)
{
	return encodeURIComponent($string, 1);
}

encodeURIComponent = window.encodeURIComponent || function($string, $encodeURI)
{
	var c, s, i = 0, $enc = [], $preserved = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_.!~*'()" + ($encodeURI ? ',/?:@&=+$' : '');

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
		$enc.push(c<128
			? s(c)
			: (
				c<2048
				? s(192+(c>>6),128+(c&63))
				: (
					c<65536
					? s(224+(c>>12),128+(c>>6&63),128+(c&63))
					: s(240+(c>>18),128+(c>>12&63),128+(c>>6&63),128+(c&63))
				)
			));
	}

	$enc = $enc.join('');
	$string = [];

	for (i = 0; i<$enc.length; ++i)
		$string.push($preserved.indexOf($enc.charAt(i))==-1
			? '%'+$enc.charCodeAt(i).toString(16).toUpperCase()
			: $enc.charAt(i));

	return $string.join('');
}

/* push */
if (!([].push && [].push(0))) Array.prototype.push = function()
{
	var $this = this, $argv = $this.push.arguments, $i = 0;
	for (; $i < $argv.length ; ++$i) $this[$this.length] = $argv[$i];
	return $this.length;
}

/* pop */
Array.prototype.pop = Array.prototype.pop || function()
{
	var $this = this;

	return $this.length ? $this[$this.length--] : null;
}

/* shift */
Array.prototype.shift = Array.prototype.shift || function()
{
	var $this = this,
		$firstElement = $this[0];

	if ($this.length)
	{
		$this.reverse();
		--$this.length;
		$this.reverse();
	}
	else $firstElement = null;

	return $firstElement;
}

/* unshift */
Array.prototype.unshift = Array.prototype.unshift || function()
{
	var $this = this,
		$argv = $this.unshift.arguments,
		$i = $argv.length,
		$len = $this.length;

	$this.reverse();
	while (--$i>=0) $this[$this.length] = $argv[$i];
	$this.reverse();

	return $len;
}

/* splice */
if(!Array.prototype.splice || ![0].splice(0)) Array.prototype.splice = function($index, $count)
{
	var $this = this, $argv = $this.splice.arguments, $i, $removeArray, $endArray;

	if ($argv.length == 0) return $index;

	$index = $index/1 || 0;

	if ($index < 0) $index = Math.max(0, $this.length + $index);
	if ($index > $this.length)
	{
		if ($argv.length > 2) $index = $this.length;
		else return [];
	}

	if ($argv.length < 2) $count = $this.length - $index;

	$count = Math.max(0, $count/1 || 0);

	$removeArray = $this.slice($index, $index + $count);
	$endArray = $this.slice($index + $count);

	$this.length = $index;

	for ($i = 2; $i < $argv.length; ++$i) $this[$this.length] = $argv[$i];
	for ($i = 0; $i < $endArray.length; ++$i) $this[$this.length] = $endArray[$i];

	return $removeArray;
}

/* continue loading */
loadW();
