<?php

class
{
	protected static $map = array(
		'conf'    => 'apache',
		'html'    => 'html4strict',
		'js'      => 'javascript',
		'sh'      => 'bash',
		'tex'     => 'latex',
		'ptl'     => 'html4strict',
		'ptl/js'  => 'javascript',
		'ptl/css' => 'css',
	);

	static function highlight($a, $language, $line_numbers)
	{
		isset(self::$map[$language]) && $language = self::$map[$language];

		$a = new geshi($a, $language);
		$a->set_encoding('UTF-8');
		$line_numbers && $a->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
		$a->set_header_type(GESHI_HEADER_DIV);
		$a->set_tab_width(4);

		return $a->parse_code();
	}
}
