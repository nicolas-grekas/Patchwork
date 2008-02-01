<?php

class extends agent_pStudioWidget_reader_gif
{
	protected

	$language = 'php',
	$template = 'pStudioWidget/reader/php';


	function compose($o)
	{
		$o = agent_pStudioWidget_reader::compose($o);

		if (isset($o->data) && $o->data)
		{
			$geshi = new geshi($o->data, $this->language);
			$o->data = $geshi->parse_code();
		}

		return $o;
	}
}
