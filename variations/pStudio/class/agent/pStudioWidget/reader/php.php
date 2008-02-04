<?php

class extends agent_pStudioWidget_reader
{
	protected

	$language = 'php',
	$template = 'pStudioWidget/reader/php';


	function compose($o)
	{
		$o = parent::compose($o);

		if (isset($o->data) && false !== $o->data)
		{
			$geshi = new geshi($o->data, $this->language);
			$geshi->set_encoding('UTF-8');
			$geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
			$geshi->set_header_type(GESHI_HEADER_DIV);
			$geshi->set_tab_width(4);
			$o->data = $geshi->parse_code();
		}

		return $o;
	}
}
