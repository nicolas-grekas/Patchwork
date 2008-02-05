<?php

class extends agent_pStudioWidget_reader_php
{
	protected

	$rawContentType = 'image/gif',
	$template = 'pStudioWidget/reader/gif';

	function compose($o)
	{
		list($o->width, $o->height) = getimagesize($this->realpath);

		$o->filesize  = filesize($this->realpath);
		$o->extension = $this->extension;

		return $o;
	}
}
