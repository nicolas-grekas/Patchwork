<?php

class pipe_truncate
{
	static function php($string, $length = 80, $etc = '...', $break_words = false)
	{
		$string = CIA::string($string);
		$length = CIA::string($length);
		$etc = CIA::string($etc);
		$break_words = CIA::string($break_words);

		if (!$length) return '';

		if (mb_strlen($string) > $length)
		{
			$length -= mb_strlen($etc);
			if (!$break_words) $string = preg_replace('/\s+?(\S+)?$/u', '', mb_substr($string, 0, $length + 1));
	  
			return mb_substr($string, 0, $length) . $etc;
		}
	
		return $string;
	}

	static function js()
	{
		?>/*<script>*/

root.P<?php echo substr(__CLASS__, 5)?> = function($string, $length, $etc, $break_words)
{
	$string = str($string);
	$length = str($length, 80);
	$etc = str($etc, '...');

	if (!$length) return '';

	if ($string.length > $length)
	{
		$length -= strlen($etc);
		if (!str($break_words)) $string = $string.substr(0, $length + 1).replace(/\s+?(\S+)?$/g, '');
	  
		return substr($string, 0, $length) . $etc;
	}
	
	return $string;
}

<?php	}
}
