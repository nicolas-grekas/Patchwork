<?php

class pipe_printf
{
	static function php($format)
	{
		$a = func_get_args();
		$a = array_map(array('CIA', 'string'), $a);
		return call_user_func_array('sprintf', $a);
	}

	static function js()
	{
		?>/*<script>*/

root.P$<?php echo substr(__CLASS__, 5)?> = function($format)
{
	$format = str($format);

	var $rx = /^([^%]*)%((%%)*)((\d+)\$)?(-)?('.|0|\x20)?(-)?(\d*)(\.(\d*))?([%bcdeufFosxX]?)(.*)$/,
		    //  1       2       5        6   7           8   9       11     12             13
		$str = '',
		$idCounter = 0,
		$match, $type, $Math = Math;

	while ($match = $rx.exec($format))
	{
		$str += $match[1] + $match[2].substr(0, $match[2].length/2);

		$type = $match[12];
		$format = $match[13];

		if ($type=='%') $param = $type;
		else if ($type)
		{
			var $base = 0,
				$param = arguments[ $match[4] ? $match[5]-0 : ++$idCounter ],
				$iParam = parseInt($param) || 0;

			switch ($type)
			{
				case 'b': $param = $iParam.toString(2); break;
			 	case 'c': $iParam = String.fromCharCode($iParam);
				case 'd': $param = $iParam; break;
				case 'u': $param = ''+$Math.abs($iParam); break;
				case 'e':
					$iParam = 0;
					while ($param>9) $param /= 10, ++$iParam;
					while ($param<1) $param *= 10, --$iParam;
					$match[11] = 5;
				case 'f':
				case 'F':
					$base = $match[11] ? $match[11]-0 : 6;
					$param = $Math.round($param * $Math.pow(10, $base)) || (''+$Math.pow(10, $base+1)).substr(1);
					$param = '' + $param;
					if ($base) $base = $param.length - $base, $param = ($param.substr(0, $base)||0) + '.' + $param.substr($base);
					if ($type == 'e') $param += 'e' + ($iParam>=0 ? '+'+$iParam : $iParam);
					break;
				case 'x':
				case 'X': $base = 8;
				case 'o': $base += 8;
					$param = $iParam.toString($base).toLowerCase();
					if ($type == 'X') $param = $param.toUpperCase();
			}

			$param += '';
			$type = $match[7] || ' ';
			$type = $type.charAt(1) || $type;

			while ($param.length < $match[9])
				if ($match[6] || $match[8]) $param += $type;
				else $param = $type + $param;
		}
		else
			$format = $format.substr(1),
			$param = $type;

		$str += $param;
	}

	return $str + $format;
}

<?php	}
}
