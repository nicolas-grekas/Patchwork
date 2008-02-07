<?php

class extends agent_pStudioWidget_reader
{
	protected $rawContentType = 'audio/mpeg';

	function compose($o)
	{
		$o->extension = $this->extension;

		return $o;
	}
}

