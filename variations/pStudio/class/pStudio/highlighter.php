<?php

class
{
	static function highlight($a, $language, $line_numbers)
	{
		$language = 'highlight_' . patchwork_class2file($language);

		return method_exists(__CLASS__, $language)
			? self::$language($a, $line_numbers)
			: self::highlight_txt($a, $line_numbers);
	}


	protected static function highlight_txt($a, $line_numbers)
	{
		$a = htmlspecialchars($a);
		$a = nl2br($a);

		return self::finalize($a, $line_numbers);
	}

	protected static function finalize($a, $line_numbers)
	{
		$a = str_replace("\t", '    ' , $a);
		$a = str_replace('  ' , ' &nbsp;', $a);

		if ($line_numbers)
		{
			$a = preg_replace("'^.*$'m", '<li><code>$0</code></li>', $a);
			$a = '<ol style="font-family:monospace;">' . $a . '</ol>';
		}
		else $a = '<code>' . $a . '</code>';

		return $a;
	}


	protected static $pool;

	protected static function highlight_php($a, $line_numbers)
	{
		$a = highlight_string($a, true);
		$a = substr($a, 6, -7);
		$a = str_replace('&nbsp;' , ' ', $a);

		if ($line_numbers)
		{
			$a = str_replace("\n", '', $a);

			ob_start();

			self::$pool = array();

			foreach (explode('<br />', $a) as $a)
			{
				echo implode('', self::$pool);
				echo preg_replace_callback("'<(/?)span[^>]*>'", array(__CLASS__, 'pool_callback'), $a);
				echo str_repeat('</span>', count(self::$pool));
				echo "<br />\n";
			}

			$a = ob_get_clean();
		}

		return self::finalize($a, $line_numbers);
	}

	protected static function pool_callback($m)
	{
		if ($m[1]) array_pop(self::$pool);
		else array_push(self::$pool, $m[0]);

		return $m[0];
	}
}
