<?php

class extends agent_pStudio_reader
{
	protected $rawContentType = 'audio/mpeg';

	function compose($o)
	{
		$o->extension = $this->extension;
		$o->rawContentType = $this->rawContentType;

		return $o;
	}
}

