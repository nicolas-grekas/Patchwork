<?php

class extends agent_pStudio_opener
{
	protected $rawContentType = 'audio/mpeg';

	protected function composeReader($o)
	{
		$o->extension = $this->extension;
		$o->rawContentType = $this->rawContentType;

		return $o;
	}
}

