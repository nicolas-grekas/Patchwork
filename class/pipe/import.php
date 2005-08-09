<?php

class pipe_import
{
	static function php($pool, $varname = '')
	{
		$varname = $varname ? $varname : '_argv_';
		$var = @$pool->$varname;

		if ($var instanceof loop && CIA::string($var))
		{
			while ($i =& $var->render()) $data =& $i;
			IA::escape($data);
			foreach ($data as $k => $v) $pool->$k = $v;
		}

		unset($pool->$varname);
		return '';
	}

	static function js()
	{
		?>/*<script>*/

root.P<?php echo substr(__CLASS__, 5)?> = function($pool, $varname)
{
	$varname = $varname || '_argv_';
	var $i, $j, $var = $pool[$varname];

	if ($var && $var.a && $var/1)
	{
		$i = $var.a();
		while ($j = $i()) $var = $j;
		for ($i in $var) if ($i!='$') $pool[$i] = $var[$i];
	}

	delete $pool[$varname];
	return '';
}

<?php	}
}
