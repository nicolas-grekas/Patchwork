<?php

class extends agent_pStudioWidget_reader
{
	protected

	$rawContentType = 'audio/mpeg',
	$template = 'pStudioWidget/reader/mpg';


	function compose($o)
	{
		$o = parent::compose($o);

		$o->extension = $this->extension;

		return $o;
	}
}

