<?php

class extends agent_pStudio_reader
{
	protected $language = 'php';

	function compose($o)
	{
		$a = @file_get_contents($this->realpath);

		if (false !== $a)
		{
			$a && false !== strpos($a, "\r") && $a = strtr(str_replace("\r\n", "\n", $a), "\r", "\n");
			u::isUTF8($a) || $a = utf8_encode($a);

			$a = new geshi($a, $this->language);
			$a->set_encoding('UTF-8');
			$a->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
			$a->set_header_type(GESHI_HEADER_DIV);
			$a->set_tab_width(4);
			$o->code = $a->parse_code();
		}

		return $o;
	}
}
