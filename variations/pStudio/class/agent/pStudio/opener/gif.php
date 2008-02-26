<?php

class extends agent_pStudio_opener
{
	protected $rawContentType = 'image/gif';

	protected function composeReader($o)
	{
		list($o->width, $o->height) = getimagesize($this->realpath);

		$o->filesize  = filesize($this->realpath);
		$o->extension = $this->extension;

		return $o;
	}
}
