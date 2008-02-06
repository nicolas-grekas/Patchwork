<?php

class extends agent_pStudioWidget_reader
{
	protected

	$rawContentType = 'application/x-shockwave-flash',
	$template = 'pStudioWidget/reader/mpg';


	function compose($o)
	{
		$o = parent::compose($o);

		$o->extension = $this->extension;
		$o->rawContentType = $this->rawContentType;

		return $o;
	}
}

