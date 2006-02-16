<?php

class pipe_escape
{
	static function php($string, $type = '')
	{
		$string = CIA::string($string);
		$type = CIA::string($type);
		switch ($type)
		{
			case 'unhtml':
				return (string) $string === (string) ($string-0)
					? $string
					: str_replace(
						array('&#039;', '&quot;', '&gt;', '&lt;', '&amp;'),
						array("'"     , '"'     , '>'   , '<'   , '&'    ),
						$string
					);

			case 'url': return rawurlencode($string);

			case 'jsh': return pipe_escape::php(pipe_escape::php($string, 'js'));

			case 'js':
				return (string) $string === (string) ($string-0)
					? $string
					: ("'" . strtr(pipe_escape::php($string, 'unhtml'), array(
						'\\' => '\\\\',
						"'"  => "\\'",
						"\r" => '\\r',
						"\n" => '\\n',
						'</' => '<\/')
					) . "'");

			case 'mailto':
				$string = htmlspecialchars($string);
				return '<a href="mailto:'
					. str_replace('@', '[&#97;t]', $string) . '">'
					. str_replace('@', '<span style="display:none">@</span>&#64;', $string)
					. '</a>';

			default: return htmlspecialchars($string);
		}
	}

	static function js()
	{
		?>/*<script>*/

P$<?php echo substr(__CLASS__, 5)?> = function($string, $type)
{
	$string = str($string);
	switch (str($type))
	{
		case 'unhtml':
			return (''+$string/1==$string)
				? $string/1
				: $string.replace(
					/&#039;/g, "'").replace(
					/&quot;/g, '"').replace(
					/&gt;/g, '>').replace(
					/&lt;/g, '<').replace(
					/&amp;/g, '&'
				);

		case 'url': return eUC($string);

		case 'jsh': return P$<?php echo substr(__CLASS__, 5)?>(P$<?php echo substr(__CLASS__, 5)?>($string, 'js'));

		case 'js':
			return (''+$string/1==$string)
				? $string/1
				: ("'" + P$<?php echo substr(__CLASS__, 5)?>($string, 'unhtml').replace(
					/\\/g , '\\\\').replace(
					/'/g  , "\\'").replace(
					/\r/g , '\\r').replace(
					/\n/g , '\\n').replace(
					/<\//g, '<\/'
				) + "'");

		case 'mailto':
			$string = esc($string);
			return '<a href="mailto:' + $string + '">' + $string + '</a>';

		default: return esc($string);
	}
}

<?php	}
}
